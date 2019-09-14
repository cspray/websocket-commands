<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Promise;
use Amp\Websocket\Client;
use Amp\Websocket\Message;
use Amp\Websocket\Server\Websocket;
use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;
use Cspray\WebsocketCommands\Internal\WebsocketErrorPayload;
use function Amp\call;

/**
 * A Websocket implementation that handles all received Client by executing a WebsocketCommand based on the message
 * received in a JSON payload.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
final class CommandPoweredWebsocket extends Websocket {

    private $handshakeAuthenticator;

    /**
     * @var WebsocketCommand[]
     */
    private $commands = [];

    /**
     * @var ClientDisconnectObserver[]
     */
    private $clientDisconnectObservers = [];

    public function __construct(HandshakeAuthenticator $handshakeAuthenticator) {
        parent::__construct();
        $this->handshakeAuthenticator = $handshakeAuthenticator;
    }

    /**
     * Returns the array of WebsocketCommands that have been added to this Websocket.
     *
     * @return WebsocketCommand[]
     */
    public function getCommands() : array {
        return $this->commands;
    }

    /**
     * Remove all WebsocketCommand that have been added to this implementation.
     */
    public function clearCommands() : void {
        $this->commands = [];
    }

    /**
     * Add a set of $commands to the Websocket.
     *
     * Please be aware that passing in $commands with duplicate names effectively overwrites previous values. Generally
     * speaking all of your WebsocketCommand names should be unique.
     *
     * @param WebsocketCommand ...$commands
     */
    public function addCommands(WebsocketCommand ...$commands) : void {
        foreach ($commands as $command) {
            $this->commands[$command->getName()] = $command;
            if ($command instanceof ClientDisconnectObserver) {
                $this->addClientDisconnectObservers($command);
            }
        }
    }

    /**
     * Add an observer that will be notified when every Client is disconnected.
     *
     * @param ClientDisconnectObserver ...$clientDisconnectObservers
     */
    public function addClientDisconnectObservers(ClientDisconnectObserver ...$clientDisconnectObservers) {
        foreach ($clientDisconnectObservers as $clientDisconnectObserver) {
            $this->clientDisconnectObservers[] = $clientDisconnectObserver;
        }
    }

    /**
     * Return the collection of ClientDisconnectObservers that are currently watching Client disconnects.
     *
     * @return array
     */
    public function getClientDisconnectObservers() : array {
        return $this->clientDisconnectObservers;
    }

    /**
     * Respond to websocket handshake requests.
     * If a websocket application doesn't wish to impose any special constraints on the
     * handshake it doesn't have to do anything in this method (other than return the
     * given Response object) and all handshakes will be automatically accepted.
     * This method provides an opportunity to set application-specific headers on the
     * websocket response.
     *
     * @param Request $request The HTTP request that instigated the handshake
     * @param Response $response The HTTP response returned to the user
     *
     * @return Promise<Response> Resolve with the given Response, modify the status code to a non-2XX response to reject
     *                           the connection.
     */
    protected function onHandshake(Request $request, Response $response) : Promise {
        return call(function() use($request, $response) {
            $handshakeResponse = yield $this->handshakeAuthenticator->onHandshake($request, $response);

            return $handshakeResponse ?? $response;
        });
    }

    /**
     * Receives any Messages that the Client may send and either responds with a WebsocketErrorPayload or executes a
     * WebsocketCommand.
     *
     * There are a limited number of requirements on the data being sent from the Client:
     *
     * 1. It MUST be valid JSON. Anything that results in an error parsing the payload as JSON will result in an error
     * response.
     * 2. The JSON payload MUST have a key 'command' that matches the name of a WebsocketCommand that has been added
     * to this implementation. If the key is not provided or the value does not match a provided command name an error
     * response will be sent.
     *
     * Generally we recommend that clients follow either an adhered to standard for the team or the below format:
     *
     * [
     *      'command' => 'command-name',
     *      'data' => [ ... arbitrary data ],
     *      'meta' => [ ... meta data ]
     * ]
     *
     * Obviously your data structure can be whatever you would like as long as the command key is adhered to; the entire
     * JSON payload received from the Client will be passed to each WebsocketCommand in the form of a ClientPayload.
     *
     * If the Client Request results in an error that prevents your WebsocketCommand from executing the following data
     * structure will be returned to the Client:
     *
     *  [
     *      'error' => [
     *          'code' => ####,
     *          'message' => 'Some message',
     *      ]
     *  ]
     *
     * The 'code' will be a 4-digit number that represents the unique error encountered by this implementation. The
     * message provides as many suitable details as possible for why the error was encountered. For more information
     * about the errors that this implemenation may respond with please {@see WebsocketError}.
     *
     * @param Client $client The websocket client connection.
     * @param Request $request The HTTP request that instigated the connection.
     * @param Response $response The HTTP response sent to client to accept the connection.
     *
     * @return Promise<null>
     */
    protected function onConnect(Client $client, Request $request, Response $response) : Promise {
        $client->onClose(function(Client $client, int $code, string $reason) {
            $promises = [];
            foreach ($this->clientDisconnectObservers as $clientDisconnectObserver) {
                $promises[] = $clientDisconnectObserver->onClientDisconnect($client, $code, $reason);
            }

            return Promise\all($promises);
        });


        return call(function() use($client) {
            /** @var Message $message */
            while ($message = yield $client->receive()) {
                $rawMessage = yield $message->buffer();
                $json = json_decode($rawMessage, true);

                if (!is_array($json)) {
                    $errorPayload = new WebsocketErrorPayload(WebsocketError::InvalidJson());
                    yield $client->send(json_encode($errorPayload));
                    continue;
                }

                $clientPayload = new ClientPayload($json);

                if (!array_key_exists($clientPayload->get('command'), $this->commands)) {
                    $errorPayload = new WebsocketErrorPayload(WebsocketError::InvalidCommand());
                    yield $client->send(json_encode($errorPayload));
                    continue;
                }

                $command = $this->commands[$clientPayload->get('command')];

                yield $command->execute($client, $clientPayload);
            }
        });
    }
}