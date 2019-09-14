<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Internal;

use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;
use JsonSerializable;

/**
 * An error object that ensures a WebsocketError is serialized into the appropriate JSON payload for a client.
 *
 * @package Cspray\WebsocketCommands\Internal
 * @license See LICENSE in source root
 * @internal
 */
class WebsocketErrorPayload implements JsonSerializable {

    private $websocketError;

    public function __construct(WebsocketError $websocketError) {
        $this->websocketError = $websocketError;
    }

    /**
     * Returns an array with the following format based on the WebsocketError passed:
     *
     *  [
     *      'error' => [
     *          'code' => ###,
     *          'msg' => XXXXXXXXXX
     *      ]
     *  ]
     *
     * @return mixed A set of data about this error
     * @since 5.4.0
     */
    public function jsonSerialize() {
        return [
            'error' => [
                'code' => $this->websocketError->getErrorCode(),
                'message' => $this->websocketError->getErrorMessage()
            ]
        ];
    }

}