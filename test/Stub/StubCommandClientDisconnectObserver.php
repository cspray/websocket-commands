<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\ClientDisconnectObserver;
use Cspray\WebsocketCommands\ClientPayload;
use Cspray\WebsocketCommands\WebsocketCommand;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubCommandClientDisconnectObserver implements WebsocketCommand, ClientDisconnectObserver {

    public function onClientDisconnect(Client $client, int $code, string $reason) : Promise {
        return new Success();
    }

    public function getName() : string {
        return 'stub-websocket-command-client-disconnect-observer';
    }

    /**
     *
     *
     * @param Client $client
     * @param ClientPayload $clientPayload
     * @return Promise
     */
    public function execute(Client $client, ClientPayload $clientPayload) : Promise {
        return new Success();
    }
}