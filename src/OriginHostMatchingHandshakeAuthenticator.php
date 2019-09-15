<?php declare(strict_types=1);

namespace Cspray\WebsocketCommands;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Promise;
use function Amp\call;

/**
 * A HandshakeAuthenticator implementation that is meant to ensure the provided Request's origin matches up against a
 * set of known hosts.
 *
 * @package Cspray\WebsocketCommands
 * @license See LICENSE in source root
 */
final class OriginHostMatchingHandshakeAuthenticator implements HandshakeAuthenticator {

    private $hosts;

    public function __construct(string ...$hosts) {
        $this->hosts = $hosts;
    }

    /**
     * You may modify the Response to add headers or cookies, additionally you may change the status code of the
     * Response to a non-2XX code and the client's handshake will be rejected.
     *
     * @param Request $request
     * @param Response $response
     * @return Promise<Response>
     */
    public function onHandshake(Request $request, Response $response) : Promise {
        return call(function() use($request, $response) {
            $origin = $request->getHeader('origin');
            if (!in_array($origin, $this->hosts)) {
                $response->setStatus(Status::FORBIDDEN);
            }

            return $response;
        });
    }

}