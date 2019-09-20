<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Enum;

use Cspray\Yape\Enum;

/**
 * An object that represents how the CommandPoweredWebsocket should execute WebsocketCommandMiddleware.
 *
 * @package Cspray\WebsocketCommand\Enum
 */
final class MiddlewareChain implements Enum {

    private static $container = [];

    private $enumConstName;
    private $value;

    private function __construct(string $enumConstName, string $value) {
        $this->enumConstName = $enumConstName;
        $this->value = $value;
    }

    private static function getSingleton(...$constructorArgs) {
        $name = $constructorArgs[0];
        if (!isset(self::$container[$name])) {
            self::$container[$name] = new self(...$constructorArgs);
        }

        return self::$container[$name];
    }

    /**
     * Represents that the next WebsocketCommandMiddleware in the chain, or the WebsocketCommand itself, should be
     * executed.
     *
     * @return MiddlewareChain
     */
    public static function Continue() : MiddlewareChain {
        return self::getSingleton('Continue', 'Continue');
    }

    /**
     * Represents that all of the remaining WebsocketCommandMiddleware can be skipped and to execute the WebsocketCommand
     * next.
     *
     * @return MiddlewareChain
     */
    public static function Skip() : MiddlewareChain {
        return self::getSingleton('Skip', 'Skip');
    }

    /**
     * Represents that the WebsocketCommandMiddleware has responded to the Client, if necessary, and that no additional
     * middleware nor the WebsocketCommand itself shall be executed.
     *
     * @return MiddlewareChain
     */
    public static function ShortCircuit() : MiddlewareChain {
        return self::getSingleton('ShortCircuit', 'ShortCircuit');
    }

    public function getValue() : string {
        return $this->value;
    }

    public function equals(MiddlewareChain $middlewareChain) : bool {
        return $this === $middlewareChain;
    }

    public function toString() : string {
        return get_class($this) . '@' . $this->enumConstName;
    }

}
