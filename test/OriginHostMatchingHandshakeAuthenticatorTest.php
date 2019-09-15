<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test;

use Amp\Http\Server\Driver\Client as HttpServerClient;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\PHPUnit\AsyncTestCase;
use Cspray\WebsocketCommands\OriginHostMatchingHandshakeAuthenticator;
use League\Uri;
use function Amp\call;

/**
 *
 * @package Cspray\WebsocketCommands\Test
 * @license See LICENSE in source root
 */
class OriginHostMatchingHandshakeAuthenticatorTest extends AsyncTestCase {
    private $httpServerClient;

    private $request;
    private $response;

    public function setUp() : void {
        parent::setUp();
        $this->httpServerClient = $this->getMockBuilder(HttpServerClient::class)->getMock();
        $this->request = new Request($this->httpServerClient, 'GET', Uri\Http::createFromString('/'));
        $this->response = new Response();
    }

    public function testRequestWithMatchingHostsReturns200() {
        return call(function() {
            $this->response->setStatus(Status::OK);
            $this->request->addHeader('Origin', 'http://127.0.0.1:1337');
            $subject = new OriginHostMatchingHandshakeAuthenticator();

            /** @var Response $response */
            $response = yield $subject->onHandshake($this->request, $this->response);

            $this->assertInstanceOf(Response::class, $response);
            $this->assertSame(Status::FORBIDDEN, $response->getStatus());
        });
    }

}