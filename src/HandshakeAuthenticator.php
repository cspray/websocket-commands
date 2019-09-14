<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;

/**
 * An implementation intended to provide a variety of ways of authenticating a Client during the Websocket handshake
 * process.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
interface HandshakeAuthenticator {

    /**
     * You may modify the Response to add headers or cookies, additionally you may change the status code of the
     * Response to a non-2XX code and the client's handshake will be rejected.
     *
     * @param Request $request
     * @param Response $response
     * @return Promise<Response>
     */
    public function onHandshake(Request $request, Response $response) : Promise;

}