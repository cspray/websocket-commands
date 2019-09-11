<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\ClientDisconnectObserver;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubClientDisconnectObserver implements ClientDisconnectObserver {

    public $client;
    public $code;
    public $reason;

    public function onClientDisconnect(Client $client, int $code, string $reason) : Promise {
        $this->client = $client;
        $this->code = $code;
        $this->reason = $reason;
        return new Success();
    }
}