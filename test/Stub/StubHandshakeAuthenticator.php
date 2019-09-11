<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Success;
use Cspray\WebsocketCommands\HandshakeAuthenticator;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubHandshakeAuthenticator implements HandshakeAuthenticator {

    public function onHandshake(Request $request, Response $response) : Promise {
        return new Success($response);
    }

}