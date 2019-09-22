<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

/**
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
final class MiddlewareCollection {

    private $collection = [
        'global' => [],
        'commands' => []
    ];

    /**
     * Return a list of WebsocketCommandMiddleware that will be applied to every WebsocketCommand execution.
     *
     * @return WebsocketCommandMiddleware[]
     */
    public function getGlobalMiddlewares() : array {
        return $this->collection['global'];
    }

    /**
     * Return a map of WebsocketCommandMiddleware where the key is the WebsocketCommand::getName and the value is a
     * list of WebsocketCommandMiddleware.
     *
     * @return array
     */
    public function getCommandSpecificMiddlewares() : array {
        return $this->collection['commands'];
    }

    /**
     * @param WebsocketCommand $websocketCommand
     * @return WebsocketCommandMiddleware[]
     */
    public function getMiddlewaresForCommand(WebsocketCommand $websocketCommand) : array {
        $globalMiddlewares = $this->getGlobalMiddlewares();
        $commandMiddlewares = $this->getCommandSpecificMiddlewares()[$websocketCommand->getName()] ?? [];

        return array_merge($globalMiddlewares, $commandMiddlewares);
    }

    public function addGlobalMiddleware(WebsocketCommandMiddleware $middleware) : void {
        $this->collection['global'][] = $middleware;
    }

    public function addCommandSpecificMiddleware(WebsocketCommand $command, WebsocketCommandMiddleware $middleware) : void {
        $name = $command->getName();
        if (!isset($this->collection['commands'][$name])) {
            $this->collection['commands'][$name] = [];
        }

        $this->collection['commands'][$name][] = $middleware;

    }

}