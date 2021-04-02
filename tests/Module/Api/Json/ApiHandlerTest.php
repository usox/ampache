<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Json;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Json\ErrorHandling\JsonErrorHandler;
use Ampache\Module\Api\Json\Route\RouteRegistryInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Mockery;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Slim\Handlers\ErrorHandler;
use Slim\Middleware\ErrorMiddleware;
use Teapot\StatusCode;
use Tuupola\Middleware\JwtAuthentication;

class ApiHandlerTest extends MockeryTestCase
{
    /** @var ResponseFactoryInterface|MockInterface */
    private MockInterface $responseFactory;

    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var LoggerInterface|MockInterface */
    private MockInterface $logger;

    /** @var SapiEmitter|MockInterface */
    private MockInterface $sapiEmitter;

    /** @var RouteRegistryInterface|MockInterface */
    private MockInterface $routeRegistry;

    private ApiHandler $subject;

    public function setUp(): void
    {
        $this->responseFactory = $this->mock(ResponseFactoryInterface::class);
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->logger          = $this->mock(LoggerInterface::class);
        $this->sapiEmitter     = $this->mock(SapiEmitter::class);
        $this->routeRegistry   = $this->mock(RouteRegistryInterface::class);

        $this->subject = new ApiHandler(
            $this->responseFactory,
            $this->configContainer,
            $this->logger,
            $this->sapiEmitter,
            $this->routeRegistry
        );
    }

    public function testHandleEmitsErrorResponseOnFailure(): void
    {
        $app             = $this->mock(App::class);
        $errorMiddleware = $this->mock(ErrorMiddleware::class);
        $errorHandler    = $this->mock(ErrorHandler::class);
        $response        = $this->mock(ResponseInterface::class);

        $app->shouldReceive('setBasePath')
            ->with('/api/json')
            ->once();
        $app->shouldReceive('addBodyParsingMiddleware')
            ->withNoArgs()
            ->once();
        $app->shouldReceive('addErrorMiddleware')
            ->with(
                false,
                true,
                true,
                $this->logger
            )
            ->once()
            ->andReturn($errorMiddleware);
        $app->shouldReceive('add')
            ->with(Mockery::type(JwtAuthentication::class))
            ->once();
        $app->shouldReceive('run')
            ->withNoArgs()
            ->once()
            ->andThrow(new RuntimeException());

        $errorMiddleware->shouldReceive('getDefaultErrorHandler')
            ->withNoArgs()
            ->once()
            ->andReturn($errorHandler);

        $errorHandler->shouldReceive('forceContentType')
            ->with('application/json')
            ->once();
        $errorHandler->shouldReceive('registerErrorRenderer')
            ->with('application/json', JsonErrorHandler::class)
            ->once();

        $this->routeRegistry->shouldReceive('register')
            ->with($app)
            ->once();

        $this->responseFactory->shouldReceive('createResponse')
            ->with(StatusCode::INTERNAL_SERVER_ERROR)
            ->once()
            ->andReturn($response);

        $this->sapiEmitter->shouldReceive('emit')
            ->with($response)
            ->once();

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::JWT_INSECURE)
            ->once()
            ->andReturnFalse();
        $this->configContainer->shouldReceive('getJwtSecret')
            ->withNoArgs()
            ->once()
            ->andReturn('some-secret');

        $this->subject->handle($app);
    }
}
