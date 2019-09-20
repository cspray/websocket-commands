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
use Cspray\WebsocketCommands\Enum\MiddlewareChain;
use Cspray\WebsocketCommands\Exception\InvalidTypeException;
use Cspray\WebsocketCommands\HandshakeAuthenticator;
use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;
use Cspray\WebsocketCommands\Internal\WebsocketErrorPayload;
use Cspray\WebsocketCommands\Test\Stub\StubClientDisconnectObserver;
use Cspray\WebsocketCommands\Test\Stub\StubCommandClientDisconnectObserver;
use Cspray\WebsocketCommands\Test\Stub\StubHandshakeAuthenticator;
use Cspray\WebsocketCommands\Test\Stub\StubReceiveClient;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommand;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommandMiddlewareBadResolve;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommandMiddlewareExplicitContinue;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommandMiddlewareImplicitContinue;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommandMiddlewareShortCircuit;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommandMiddlewareSkip;
use Cspray\WebsocketCommands\Test\Support\Counter;
use Cspray\WebsocketCommands\WebsocketCommandMiddleware;
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
    private $counter;

    public function setUp() : void {
        parent::setUp();
        $this->httpServerClient = $this->getMockBuilder(HttpServerClient::class)->getMock();
        $this->handshakeAuthenticator = $this->getMockBuilder(HandshakeAuthenticator::class)->getMock();
        $this->request = new Request($this->httpServerClient, 'GET', Uri\Http::createFromString('/'));
        $this->response = new Response();
        $this->counter = new Counter();
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

        $command = new StubWebsocketCommand($this->counter);
        $subject = $this->getStubbedCommandPoweredWebsocket();
        $subject->addCommands($command);

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        // our stub command doesn't send any data if it executes successfully
        $this->assertCount(0, $client->sentData);
        $actualPayload = $command->clientPayload;

        $this->assertSame($client, $command->client);
        $this->assertInstanceOf(ClientPayload::class, $actualPayload);
        $this->assertSame(42, $actualPayload->get('data.foo.bar.baz'));
        $this->assertCount(1, $this->counter);
    }

    public function testMiddlewarePassedCorrectClientAndClientPayload() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommands($command);
        $subject->addMiddlewares($one);

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

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(2, $counter);
        $this->assertSame($client, $one->client);
        $this->assertInstanceOf(ClientPayload::class, $one->clientPayload);
        $this->assertSame(42, $one->clientPayload->get('data.foo.bar.baz'));
        $this->assertNull($one->commandClient);
        $this->assertNull($one->commandClientPayload);
    }

    public function testExecuteMultipleMiddleware() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $two = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $three = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommands($command);
        $subject->addMiddlewares($one, $two, $three);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(4, $counter);
    }

    public function testImplicitContinueRespected() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $two = new StubWebsocketCommandMiddlewareImplicitContinue($counter, $command);
        $three = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommands($command);
        $subject->addMiddlewares($one, $two, $three);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(4, $counter);
    }

    public function testSkipMiddlewareRespected() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $two = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $three = new StubWebsocketCommandMiddlewareSkip($counter, $command);
        $four = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $five = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommands($command);
        $subject->addMiddlewares($one, $two, $three, $four, $five);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(4, $counter);
    }

    public function testShortCircuitMiddlewareRespected() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $two = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $three = new StubWebsocketCommandMiddlewareShortCircuit($counter, $command);
        $four = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $five = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommands($command);
        $subject->addMiddlewares($one, $two, $three, $four, $five);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(3, $counter);
        $this->assertNull($command->client);
        $this->assertNull($command->clientPayload);
    }

    public function testGlobalAndCommandSpecificMiddlewareGetExecuted() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);
        $two = new StubWebsocketCommandMiddlewareExplicitContinue($counter, $command);

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommand($command, $two);
        $subject->addMiddlewares($one);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);

        $this->assertCount(3, $counter);
    }

    public function testMiddlewareResolvesWrongTypeThrowsException() {
        $counter = new Counter();
        $command = new StubWebsocketCommand($counter);

        $one = new StubWebsocketCommandMiddlewareBadResolve();

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommand($command, $one);

        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
        ])));

        $this->expectException(InvalidTypeException::class);
        $msg = sprintf('The resolved Promise from a %s MUST be a %s instance or be null.', WebsocketCommandMiddleware::class, MiddlewareChain::class);
        $this->expectExceptionMessage($msg);

        yield $this->executeMethod($subject, 'onConnect', $client, $this->request, $this->response);
    }

    public function testAddCommandClientDisconnectObserver() {
        $command = new StubCommandClientDisconnectObserver();

        $subject = $this->getStubbedCommandPoweredWebsocket();

        $subject->addCommand($command);

        $this->assertEquals([$command], $subject->getClientDisconnectObservers());
    }

    public function testDisconnectObserverSeesWhenAllClientsDisconnect() {
        $client = new StubReceiveClient(1, new InMemoryStream(json_encode([
            'command' => 'stub-websocket-command',
            'data' => []
        ])));

        $command = new StubWebsocketCommand($this->counter);
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