<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\Promise;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\Enum\MiddlewareChain;
use Cspray\WebsocketCommands\ClientPayload;
use Cspray\WebsocketCommands\Test\Support\Counter;
use Cspray\WebsocketCommands\WebsocketCommandMiddleware;
use function Amp\call;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubWebsocketCommandMiddlewareSkip implements WebsocketCommandMiddleware {

    public $client;
    public $clientPayload;

    public $commandClient;
    public $commandClientPayload;

    private $counter;
    private $command;

    public function __construct(Counter $counter, StubWebsocketCommand $command) {
        $this->counter = $counter;
        $this->command = $command;
    }

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
    public function handleClient(Client $client, ClientPayload $clientPayload) : Promise {
        return call(function() use($client, $clientPayload) {
            $this->client = $client;
            $this->clientPayload = $clientPayload;

            $this->commandClient = $this->command->client;
            $this->commandClientPayload = $this->command->clientPayload;

            $this->counter->increment();

            return MiddlewareChain::Skip();
        });

    }

}