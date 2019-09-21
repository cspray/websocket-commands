<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\Promise;
use Amp\Success;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\ClientPayload;
use Cspray\WebsocketCommands\Test\Support\Counter;
use Cspray\WebsocketCommands\WebsocketCommand;
use function Amp\call;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubWebsocketCommand implements WebsocketCommand {

    public $client;
    public $clientPayload;

    private $name;
    private $counter;

    public function __construct(Counter $counter, string $name = 'stub-websocket-command') {
        $this->counter = $counter;
        $this->name = $name;
    }

    public function getName() : string {
        return $this->name;
    }

    /**
     *
     *
     * @param Client $client
     * @param ClientPayload $clientPayload
     * @return Promise
     */
    public function execute(Client $client, ClientPayload $clientPayload) : Promise {
        return call(function() use($client, $clientPayload) {
            $this->client = $client;
            $this->clientPayload = $clientPayload;
            $this->counter->increment();
        });
    }

}