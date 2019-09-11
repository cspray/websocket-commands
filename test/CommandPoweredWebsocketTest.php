<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test;

use Amp\ByteStream\InMemoryStream;
use Amp\Delayed;
use Amp\Http\Server\Driver\Client as HttpServerClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Amp\Websocket\Code;
use Cspray\WebsocketCommands\ClientPayload;
use Cspray\WebsocketCommands\CommandPoweredWebsocket;
use Cspray\WebsocketCommands\HandshakeAuthenticator;
use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;
use Cspray\WebsocketCommands\Internal\WebsocketErrorPayload;
use Cspray\WebsocketCommands\Test\Stub\StubClientDisconnectObserver;
use Cspray\WebsocketCommands\Test\Stub\StubCommandClientDisconnectObserver;
use Cspray\WebsocketCommands\Test\Stub\StubHandshakeAuthenticator;
use Cspray\WebsocketCommands\Test\Stub\StubReceiveClient;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommand;
use League\Uri;
use ReflectionMethod;
use stdClass;
use function Amp\call;

/**
 *
 * @package Cspray\WebsocketCommands\Test
 * @license See LICENSE in source root
 */
class CommandPoweredWebsocketTest extends AsyncTestCase {

    private $httpServerClient;
    private $handshakeAuthenticator;

    private $request;
    private $response;

    public function setUp() : void {
        parent::setUp();
        $this->httpServerClient = $this->getMockBuilder(HttpServerClient::class)->getMock();
        $this->handshakeAuthenticator = $this->getMockBuilder(HandshakeAuthenticator::class)->getMock();
        $this->request = new Request($this->httpServerClient, 'GET', Uri\Http::createFromString('/'));
        $this->response = new Response();
    }

    public function getStubbedCommandPoweredWebsocket() {
        return new CommandPoweredWebsocket($this->getStubHandshakeAuthenticator());
    }

    public function getStubHandshakeAuthenticator() : HandshakeAuthenticator {
        return new StubHandshakeAuthenticator();
    }

    public function testOnHandshakeCallsHandshakeAuthenticator() {
        $data = new stdClass();
        $data->resolved = false;

        $this->handshakeAuthenticator->expects($this->once())
            ->method('onHandshake')
            ->with($this->request, $this->response)
            ->will(
                $this->returnCallback(function() use($data) {
                    return call(function() use($data) {
                        yield new Delayed(1);
                        $data->resolved = true;
                    });
                })
            );

        $subject = new CommandPoweredWebsocket($this->handshakeAuthenticator);

        $actualResponse = yield $this->executeMethod($subject, 'onHandshake', $this->request, $this->response);

        $this->assertTrue($data->resolved, 'Expected the Promise returned from HandshakeAuthenticator to be resolved');
        $this->assertSame($this->response, $actualResponse);
    }

    public function testOnHandshakeReturnsResponse() {
        $handshakeResponse = new Response();

        $this->handshakeAuthenticator->expects($this->once())
            ->method('onHandshake')
            ->with($this->request, $this->response)
            ->willReturn(new Success($handshakeResponse));

        $subject = new CommandPoweredWebsocket($this->handshakeAuthenticator);

        $actualResponse = yield $this->executeMethod($subject, 'onHandshake', $this->request, $this->response);

        $this->assertSame($handshakeResponse, $actualResponse);
    }

    public function testOnConnectClientReceiveInvalidJsonSendsError() {
        $client = new StubReceiveClient(1, new InMemoryStream('invalid json'));

        yield $this->executeMethod($this->getStubbedCommandPoweredWebsocket(), 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(1, $client->sentData);
        $this->assertSame(json_encode(new WebsocketErrorPayload(WebsocketError::InvalidJson())), $client->sentData[0]);
    }

    public function testOnConnectClientReceiveSendsInvalidCommandError() {
        $client = new StubReceiveClient(1, new InMemoryStream(json_encode(['command' => 'invalid-command'])));

        yield $this->executeMethod($this->getStubbedCommandPoweredWebsocket(), 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(1, $client->sentData);
        $this->assertSame(json_encode(new WebsocketErrorPayload(WebsocketError::InvalidCommand())), $client->sentData[0]);
    }

    public function testOnConnectClientReceiveSendsValidCommandInvokesExecuteWithCorrectPayload() {
        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
            'data' => [
                'foo' => [
                    'bar' => [
                        'baz' => 42
                    ]
                ]
            ]
        ])));

        $command = new StubWebsocketCommand();
        $subject = $this->getStubbedCommandPoweredWebsocket();
        $subject->addCommands($command);

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        // our stub command doesn't send any data if it executes successfully
        $this->assertCount(0, $client->sentData);
        $actualPayload = $command->clientPayload;

        $this->assertSame($client, $command->client);
        $this->assertInstanceOf(ClientPayload::class, $actualPayload);
        $this->assertSame(42, $actualPayload->get('data.foo.bar.baz'));
    }

    public function testDisconnectObserverSeesWhenAllClientsDisconnect() {
        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
            'data' => []
        ])));

        $command = new StubWebsocketCommand();
        $subject = $this->getStubbedCommandPoweredWebsocket();
        $subject->addCommands($command);

        $stubDisconnectObserver = new StubClientDisconnectObserver();
        $subject->addClientDisconnectObservers($stubDisconnectObserver);

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        /** @var callable|null $disconnectCallback */
        $disconnectCallback = $client->onClose;

        $this->assertNull($stubDisconnectObserver->client);
        $this->assertNotNull($disconnectCallback);

        yield $disconnectCallback($client, Code::NORMAL_CLOSE, 'Normal close');

        $this->assertSame($client, $stubDisconnectObserver->client);
        $this->assertSame(Code::NORMAL_CLOSE, $stubDisconnectObserver->code);
        $this->assertSame('Normal close', $stubDisconnectObserver->reason);
    }

    public function testCommandThatIsClientDisconnectObserversAddedToObservers() {
        $command = new StubCommandClientDisconnectObserver();
        $subject = $this->getStubbedCommandPoweredWebsocket();
        $subject->addCommands($command);

        $this->assertCount(1, $subject->getClientDisconnectObservers());
        $this->assertSame($command, $subject->getClientDisconnectObservers()[0]);
    }

    private function executeMethod(CommandPoweredWebsocket $commandPoweredWebsocket, string $method, ...$methodArgs) {
        $reflectionMethod = new ReflectionMethod(CommandPoweredWebsocket::class, $method);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invokeArgs($commandPoweredWebsocket, $methodArgs);
    }


}