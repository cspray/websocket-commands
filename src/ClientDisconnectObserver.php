<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Promise;
use Amp\Websocket\Client;

/**
 * An interface for implementations that would like to be notified when a Client has disconnected.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
interface ClientDisconnectObserver {

    /**
     * @param Client $client
     * @param int $code
     * @param string $reason
     * @return Promise
     */
    public function onClientDisconnect(Client $client, int $code, string $reason) : Promise;

}