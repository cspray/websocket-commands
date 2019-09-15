# Websocket Commands

A micro-framework to facilitate building applications using [amphp/websocket-server].

## Installation

We recommend using [Composer] to install this library.

```
composer require cspray/websocket-commands
```

## Requirements

- PHP +7.2

## How it works

### WebsocketCommand

This is the heart of the library and where you should be implementing most of your app's functionality. There are just 2 
methods to implement. `WebsocketCommand::getName` is a simple string that is the name of the command and what clients 
will have to use to request execution of the command. We'll discuss a little bit later in more details about how a 
Client would execute a command. For now, let's take a closer look at `WebsocketCommand::execute`.

This method is where the fun actually happens. It will receive an `Amp\Websocket\Client` and a 
`Cspray\WebsocketCommand\ClientPayload`. You need to return a Promise that resolves when the command is done executing. 
It is not necessary to resolve any value with the Promise and any resolved value will be ignored.

### CommandPoweredWebsocket

This is a `Amp\Websocket\Server\Websocket` implementation that adds the ability to add WebsocketCommand, add 
ClientDisconnectObservers, and potentially more extendable functionality in the future (e.g. WebsocketCommandMiddleware).
Generally speaking you should be able to simply instantiate this class, pass in the required WebsocketCommand, and 
then set it to respond to the appropriate requests on your Amp http-server. This implementation will interrogate 
connected clients websocket data and either respond with an appropriate error message or execute a WebsocketCommand that 
has been added to this implementation.

### ClientDisconnectObserver

It is often necessary to know when a Client has disconnected when creating a Websocket application. Implementations of 
this interface, that are also appropriately added to the CommandPoweredWebsocket, will be notified when _any_ Client has 
disconnected.

### Client Execution

You have implemented your WebsocketCommand, added your ClientDisconnectObservers, started up your amp http-server, and 
your Client is now ready to start executing commands. Below we will demonstrate sending off an example Client request to 
execute an echo command, which we'll show the implementation for later.

```js
const websocket = new Websocket('...');

// your websocket handlers

websocket.send(JSON.stringify({
    command: 'echo',
    data: {
        param: 'spit this back'
    }    
}));
```

The 2 things to take note of here are; 1) the Client MUST send a JSON payload or an error message will be returned and 
no WebsocketCommand will be executed and 2) the Client MUST set a `command` key within that payload that specifies the 
name of the command to execute.

## Example

For our example we will implement a simple echo command that repeats back the value of a specific data parameter. The 
ClientPayload we expect to retrieve is shown in the Use Guide above.

```php
<?php

use Amp\ByteStream\ResourceOutputStream;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Promise;
use Amp\Websocket\Client;
use Cspray\WebsocketCommands\ClientPayload;
use Cspray\WebsocketCommands\CommandPoweredWebsocket;
use Cspray\WebsocketCommands\OriginHostMatchingHandshakeAuthenticator;use Cspray\WebsocketCommands\WebsocketCommand;
use Monolog\Logger;

use function Amp\call;
use function Amp\Socket\listen;

class EchoCommand implements WebsocketCommand {

    public function getName() : string {
        return 'echo';
    }

    public function execute(Client $client, ClientPayload $clientPayload) : Promise {
        return call(function() use($client, $clientPayload) {
            $echoParam = $clientPayload->get('data.param');
            if (empty($echoParam)) {
                $client->send('Sorry , I did not get anything to echo');
            } else {
                $client->send($echoParam);
            }       
        }); 
    }

}

$hosts = ['http://127.0.0.1:1337', 'http://localhost:1337', 'http://[::1]:1337'];
$handshakeAuthenticator = new OriginHostMatchingHandshakeAuthenticator($hosts)
$websocket = new CommandPoweredWebsocket($handshakeAuthenticator);

$websocket->addCommands(new EchoCommand());

$router = new Router();
$router->addRoute('GET', '/app', $websocket);
$router->setFallback(new DocumentRoot(__DIR__ . '/public'));

$logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
$logHandler->setFormatter(new ConsoleFormatter());
$logger = new Logger('websocket-commands');
$logger->pushHandler($logHandler);

$sockets = [listen('127.0.0.1:1337'), listen('[::1]:1337'))];

$server = new Server($sockets, $router, $logger);

Loop::run(function() use ($server) {
    yield $server->start();
});
```

[amphp/websocket-server]: https://github.com/amphp/websocket-server
[Composer]: https://getcomposer.org