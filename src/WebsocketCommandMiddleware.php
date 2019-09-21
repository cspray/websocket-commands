<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Promise;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\Enum\MiddlewareChain;

/**
 * A middleware that will run before a WebsocketCommand is executed and allows the ability to modify the ClientPayload,
 * short-circuit execution of the WebsocketCommand any anything else you'd generally do in traditional middleware.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
interface WebsocketCommandMiddleware {

    /**
     * Handle that a Client has requested a WebsocketCommand with the given ClientPayload; you may modify the
     * ClientPayload object.
     *
     * The Promise returned SHOULD resolve with a MiddlewareChain instance describing what should happen with the next
     * WebsocketCommandMiddleware that would be executed. Generally speaking this library prefers explicitness over
     * implicitness, however you may also resolve the Promise with null which is an implicit MiddlewareChain::Continue.
     * Any value other than a MiddlewareChain instance or null will result in an InvalidTypeException.
     *
     * @param Client $client
     * @param ClientPayload $clientPayload
     * @return Promise<MiddlewareChain|null>
     */
    public function handleClient(Client $client, ClientPayload $clientPayload) : Promise;

}