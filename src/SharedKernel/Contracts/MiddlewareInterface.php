<?php

declare(strict_types=1);

namespace HdmBoot\SharedKernel\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Base Middleware Interface.
 *
 * Extends PSR-15 MiddlewareInterface with additional application-specific methods.
 */
interface MiddlewareInterface extends PsrMiddlewareInterface
{
    /**
     * Process an incoming server request.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;
}
