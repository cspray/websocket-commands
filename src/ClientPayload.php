<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Adbar\Dot;

/**
 * An object that is meant to be a convenient way to access an arbitrary JSON structure sent from a client.
 *
 * Please note that this object extends from Dot to provide an easier means of retrieving data from an arbitrary array.
 * Please see {@link https://github.com/adbario/php-dot-notation} for more information.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
final class ClientPayload extends Dot {}