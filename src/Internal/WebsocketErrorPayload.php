<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Internal;

use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;

/**
 *
 * @package Cspray\WebsocketCommands\Internal
 * @license See LICENSE in source root
 * @internal
 */
class WebsocketErrorPayload {

    private $websocketError;

    public function __construct(WebsocketError $websocketError) {
        $this->websocketError = $websocketError;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
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