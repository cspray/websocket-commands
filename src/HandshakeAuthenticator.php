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
     * @param Request $request
     * @param Response $response
     * @return Promise
     *
     * @todo Update documentation after clarification on returning a new Response
     */
    public function onHandshake(Request $request, Response $response) : Promise;

}