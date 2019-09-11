<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Stub;

use Amp\ByteStream\InputStream;
use Amp\Promise;
use Amp\Socket\SocketAddress;
use Amp\Socket\TlsInfo;
use Amp\Success;
use Amp\Websocket\Client;
use Amp\Websocket\ClientMetadata;
use Amp\Websocket\ClosedException;
use Amp\Websocket\Code;
use Amp\Websocket\Message;
use Amp\Websocket\Options;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Stub
 * @license See LICENSE in source root
 */
class StubReceiveClient implements Client {

    private $id;
    private $receiveStreams = [];

    public $sentData = [];
    public $sentBinary = [];
    public $sentStream = [];
    public $sentStreamBinary = [];
    public $pingCounter = 0;
    public $closeCode;
    public $closeReason;
    public $onClose;

    public function __construct(int $id, InputStream ...$inputStream) {
        $this->id = $id;
        $this->receiveStreams = $inputStream;
    }


    /**
     * Receive a message from the remote Websocket endpoint.
     *
     * @return Promise<Message|null> Resolves to message sent by the remote.
     *
     * @throws ClosedException Thrown if the connection is closed.
     */
    public function receive() : Promise {
        if (!empty($this->receiveStreams)) {
            $stream = array_pop($this->receiveStreams);
            return new Success(new Message($stream, false));
        }

        return new Success();
    }

    /**
     * @return int Unique identifier for the client.
     */
    public function getId() : int {
        return $this->id;
    }

    /**
     * @return bool True if the client is still connected, false otherwise. Returns false as soon as the closing
     *     handshake is initiated by the server or client.
     */
    public function isConnected() : bool {
        return true;
    }

    /**
     * @return SocketAddress Local socket address.
     */
    public function getLocalAddress() : SocketAddress {
        return new SocketAddress('localhost', 1337);
    }

    /**
     * @return SocketAddress Remote socket address.
     */
    public function getRemoteAddress() : SocketAddress {
        return new SocketAddress('localhost', 1337);
    }

    /**
     * @return TlsInfo|null TlsInfo object if connection is secure.
     */
    public function getTlsInfo() : ?TlsInfo {
        return null;
    }

    /**
     * @return int Number of pings sent that have not been answered.
     */
    public function getUnansweredPingCount() : int {
        return 0;
    }

    /**
     * @return int Client close code (generally one of those listed in Code, though not necessarily).
     *
     * @throws \Error Thrown if the client has not closed.
     */
    public function getCloseCode() : int {
        throw new \Error('The client has not closed');
    }

    /**
     * @return string Client close reason.
     *
     * @throws \Error Thrown if the client has not closed.
     */
    public function getCloseReason() : string {
        throw new \Error('The client has not closed');
    }

    /**
     * @return bool True if the peer initiated the websocket close.
     *
     * @throws \Error Thrown if the client has not closed.
     */
    public function didPeerInitiateClose() : bool {
        throw new \Error('The client has not closed');
    }

    /**
     * Sends a text message to the endpoint. All data sent with this method must be valid UTF-8. Use `sendBinary()` if
     * you want to send binary data.
     *
     * @param string $data Payload to send.
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     *
     * @throws ClosedException Thrown if sending to the client fails.
     */
    public function send(string $data) : Promise {
        $this->sentData[] = $data;
        return new Success();
    }

    /**
     * Sends a binary message to the endpoint.
     *
     * @param string $data Payload to send.
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     *
     * @throws ClosedException Thrown if sending to the client fails.
     */
    public function sendBinary(string $data) : Promise {
        $this->sentBinary[] = $data;
        return new Success();
    }

    /**
     * Streams the given UTF-8 text stream to the endpoint. This method should be used only for large payloads such as
     * files. Use send() for smaller payloads.
     *
     * @param InputStream $stream
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     *
     * @throws ClosedException Thrown if sending to the client fails.
     */
    public function stream(InputStream $stream) : Promise {
        $this->sentStream[] = $stream;
        return new Success();
    }

    /**
     * Streams the given binary to the endpoint. This method should be used only for large payloads such as
     * files. Use sendBinary() for smaller payloads.
     *
     * @param InputStream $stream
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     *
     * @throws ClosedException Thrown if sending to the client fails.
     */
    public function streamBinary(InputStream $stream) : Promise {
        $this->sentStreamBinary[] = $stream;
        return new Success();
    }

    /**
     * Sends a ping to the endpoint.
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     */
    public function ping() : Promise {
        $this->pingCounter++;
        return new Success();
    }

    /**
     * @return Options The options object associated with this client.
     */
    public function getOptions() : Options {
        return Options::createServerDefault();
    }

    /**
     * Returns connection metadata.
     *
     * @return ClientMetadata
     */
    public function getInfo() : ClientMetadata {
        return new ClientMetadata(time(), false);
    }

    /**
     * Closes the client connection.
     *
     * @param int $code
     * @param string $reason
     *
     * @return Promise<int> Resolves with the number of bytes sent to the other endpoint.
     */
    public function close(int $code = Code::NORMAL_CLOSE, string $reason = '') : Promise {
        $this->closeCode = $code;
        $this->closeReason = $reason;
        return new Success();
    }

    /**
     * Attaches a callback invoked when the client closes. The callback is passed this object as the first parameter,
     * the close code as the second parameter, and the close reason as the third parameter.
     *
     * @param callable(Client $client, int $code, string $reason) $callback
     */
    public function onClose(callable $callback) : void {
        $this->onClose = $callback;
    }
}