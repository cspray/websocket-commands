<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use JsonSerializable;

/**
 * An implementation that represents some data that we want to send from the server to the client.
 *
 * The only supported data protocol that this library accepts is JSON. While your application may not adhered to this
 * standard for data sent to the client it is enforced on reception and consistency matters. If you decide to use any
 * of the convenience of helper functionality that this library provides, e.g. WebsocketMultiCaster, it will expect a
 * ServerPayload as the data to send to the Client.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
interface ServerPayload extends JsonSerializable {}