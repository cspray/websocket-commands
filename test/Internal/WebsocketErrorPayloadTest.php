<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands\Test\Internal;

use Cspray\WebsocketCommands\Internal\Enum\WebsocketError;
use Cspray\WebsocketCommands\Internal\WebsocketErrorPayload;
use PHPUnit\Framework\TestCase;

/**
 *
 * @package Cspray\WebsocketCommands\Test\Internal
 * @license See LICENSE in source root
 */
class WebsocketErrorPayloadTest extends TestCase {

    public function testInvalidJsonError() {
        $payload = new WebsocketErrorPayload(WebsocketError::InvalidJson());

        $expected = [
            'error' => [
                'code' => 1000,
                'message' => 'Client payloads MUST be valid JSON.'
            ]
        ];

        $this->assertSame($expected, $payload->jsonSerialize());
    }

    public function testInvalidCommandError() {
        $payload = new WebsocketErrorPayload(WebsocketError::InvalidCommand());

        $expected = [
            'error' => [
                'code' => 1001,
                'message' => 'The command requested by the client is invalid.'
            ]
        ];

        $this->assertSame($expected, $payload->jsonSerialize());
    }


}