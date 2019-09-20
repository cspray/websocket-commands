<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Support;

use Countable;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Support
 * @license See LICENSE in source root
 */
final class Counter implements Countable {

    private $counter = 0;

    public function increment() : void {
        $this->counter++;
    }

    /**
     * The number of times this Counter has been incremented.
     */
    public function count() {
        return $this->counter;
    }

}