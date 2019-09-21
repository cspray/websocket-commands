# Changelog

## 1.2.0 - 20??-??-??

- Add a `WebsocketMulticaster` that will allow for multicasting to a pool of clients without the need to ask for the Websocket directly.
- Add a `WebsocketBroadcaster` that will allow for broadcasting to all clients without the need to ask for the Websocket directly.
- Implement better logging within the `CommandPoweredWebsocket`.

### Added

- Added a `WebsocketCommandMiddleware` that allows for the manipulation of the ClientPayload and possible short circuiting of other middlewares.
- Added a `MiddlewareChain` enum that explicitly controls how remaining middleware will be invoked; currently you can continue execution, 
skip remaining middleware and execute command, or short circuit remaining middleware and DO NOT execute the command.
- Added a `MiddlewareCollection` data structure that stores the global and command specific middleware.
- Adds new methods on to the `CommandPoweredWebsocket` to facilitate adding global middleware 

## 1.1.0 - 2019-9-15

### Added

- Added an `OriginHostMatchingHandshakeAuthenticator` to provide a minimal implementation needed to get started.
- Improved documentation in README and within source code.

### Fixed

- Ensures that the WebsocketErrorPayload properly implements the `JsonSerializable` interface.

## 1.0.1 - 2019-9-14

### Fixed

- Removed an erroneous interface implementation from the WebsocketErrorPayload internal class.

## 1.0.0 - 2019-9-10

### Added

- Adds a `HandshakeAuthenticator` interface that abstracts away the act of authorizing a websocket connection.
- Adds a `WebsocketCommand` interface where implementations are commands that may be executed through a Websocket Client.
- Adds a `ClientDisconnectObserver` interface where implementations are notified when any Client has disconnected.
- Adds the `CommandPoweredWebsocket` implementation with the ability to attach `WebsocketCommand` and `ClientDisconnectObserver`.
- Adds a `ClientPayload` object that facilitates reading arbitrary JSON data through the `adbario/php-dot-notation` library.