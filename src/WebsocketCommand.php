<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Promise;
use Amp\Websocket\Client;

/**
 * An interface which represents a discrete set of operations that can be executed by a user via a Websocket connection.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
interface WebsocketCommand {

    /**
     * Return the name that the client must use to execute this WebsocketCommand.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Execute whatever is required for this command and return a Promise that resolves when it is complete.
     *
     * @param Client $client
     * @param ClientPayload $clientPayload
     * @return Promise
     */
    public function execute(Client $client, ClientPayload $clientPayload) : Promise;

}