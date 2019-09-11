<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Internal\Enum;

use Cspray\Yape\Enum;

/**
 * Class WebsocketError
 *
 * @package Cspray\WebsocketCommands\Internal\Enum
 * @internal
 */
final class WebsocketError implements Enum {

    private const INVALID_JSON_CODE = 1000;
    private const INVALID_COMMAND_CODE = 1001;

    private static $container = [];

    private $enumConstName;
    private $value;

    private $errorCode;
    private $errorMessage;

    private function __construct(string $enumConstName, string $value, int $code, string $message) {
        $this->enumConstName = $enumConstName;
        $this->value = $value;
        $this->errorCode = $code;
        $this->errorMessage = $message;
    }

    private static function getSingleton(...$constructorArgs) {
        $name = $constructorArgs[0];
        if (!isset(self::$container[$name])) {
            self::$container[$name] = new self(...$constructorArgs);
        }

        return self::$container[$name];
    }

    public static function InvalidJson() : WebsocketError {
        return self::getSingleton(
            'InvalidJson',
            'InvalidJson',
            self::INVALID_JSON_CODE,
            'Client payloads MUST be valid JSON.'
        );
    }

    public static function InvalidCommand() : WebsocketError {
        return self::getSingleton(
            'InvalidCommand',
            'InvalidCommand',
            self::INVALID_COMMAND_CODE,
            'The command requested by the client is invalid.'
        );
    }

    public function getValue() : string {
        return $this->value;
    }

    public function getErrorCode() : int {
        return $this->errorCode;
    }

    public function getErrorMessage() : string {
        return $this->errorMessage;
    }

    public function equals(WebsocketError $websocketError) : bool {
        return $this === $websocketError;
    }

    public function toString() : string {
        return get_class($this) . '@' . $this->enumConstName;
    }

}
