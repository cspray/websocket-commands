<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test;

use Cspray\WebsocketCommands\MiddlewareCollection;
use Cspray\WebsocketCommands\Test\Stub\StubWebsocketCommand;
use Cspray\WebsocketCommands\Test\Support\Counter;
use Cspray\WebsocketCommands\WebsocketCommand;
use Cspray\WebsocketCommands\WebsocketCommandMiddleware;
use PHPUnit\Framework\TestCase;

/**
 *
 * @package Cspray\WebsocketCommands\Test
 * @license See LICENSE in source root
 */
class MiddlewareCollectionTest extends TestCase {

    public function testGetGlobalMiddlewaresEmpty() {
        $subject = new MiddlewareCollection();

        $this->assertEmpty($subject->getGlobalMiddlewares());
    }

    public function testGetGlobalMiddlewaresNotEmpty() {
        $subject = new MiddlewareCollection();

        $one = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();
        $two = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();
        $three = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();

        $subject->addGlobalMiddleware($one);
        $subject->addGlobalMiddleware($two);
        $subject->addGlobalMiddleware($three);

        $this->assertSame([$one, $two, $three], $subject->getGlobalMiddlewares());
    }

    public function testGetCommandSpecificMiddlewaresEmpty() {
        $subject = new MiddlewareCollection();

        $this->assertEmpty($subject->getCommandSpecificMiddlewares());
    }

    public function testGetCommandSpecificMiddlewaresNotEmpty() {
        $subject = new MiddlewareCollection();
        $counter = new Counter();
        $oneCommand = new StubWebsocketCommand($counter, 'one');
        $twoCommand = new StubWebsocketCommand($counter, 'two');

        $oneMiddleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();
        $twoMiddleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();
        $threeMiddleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();

        $subject->addCommandSpecificMiddleware($oneCommand, $oneMiddleware);
        $subject->addCommandSpecificMiddleware($oneCommand, $twoMiddleware);
        $subject->addCommandSpecificMiddleware($twoCommand, $threeMiddleware);

        $expected = ['one' => [$oneMiddleware, $twoMiddleware], 'two' => [$threeMiddleware]];
        $actual = $subject->getCommandSpecificMiddlewares();

        $this->assertSame($expected, $actual);
    }

    public function testGetMiddlewaresForCommandEmptyGlobalAndEmptyCommandSpecific() {
        $subject = new MiddlewareCollection();
        $command = new StubWebsocketCommand(new Counter());

        $actual = $subject->getMiddlewaresForCommand($command);

        $this->assertEmpty($actual);
    }

    public function testGetMiddlewaresForCommandNotEmptyGlobalAndEmptyCommandSpecific() {
        $subject = new MiddlewareCollection();
        $command = new StubWebsocketCommand(new Counter());

        $middleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();

        $subject->addGlobalMiddleware($middleware);

        $actual = $subject->getMiddlewaresForCommand($command);

        $this->assertSame([$middleware], $actual);
    }

    public function testGetMiddlewaresForCommandEmptyGlobalAndNotEmptyCommandSpecific() {
        $subject = new MiddlewareCollection();
        $command = new StubWebsocketCommand(new Counter());

        $middleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();

        $subject->addCommandSpecificMiddleware($command, $middleware);

        $actual = $subject->getMiddlewaresForCommand($command);

        $this->assertSame([$middleware], $actual);
    }

    public function testGetMiddlewaresForCommandNotEmptyGlobalAndNotEmptyCommandSpecific() {
        $subject = new MiddlewareCollection();
        $command = new StubWebsocketCommand(new Counter());

        $globalMiddleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();
        $commandMiddleware = $this->getMockBuilder(WebsocketCommandMiddleware::class)->getMock();

        $subject->addGlobalMiddleware($globalMiddleware);
        $subject->addCommandSpecificMiddleware($command, $commandMiddleware);

        $actual = $subject->getMiddlewaresForCommand($command);

        $this->assertSame([$globalMiddleware, $commandMiddleware], $actual);

        // make sure that above operation didn't accidentally mutate the state of the collection
        $this->assertSame([$globalMiddleware], $subject->getGlobalMiddlewares());
        $this->assertSame(['stub-websocket-command' => [$commandMiddleware]], $subject->getCommandSpecificMiddlewares());
    }

}