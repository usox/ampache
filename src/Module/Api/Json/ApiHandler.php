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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Api\Json;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Json\ErrorHandling\JsonErrorHandler;
use Ampache\Module\Api\Json\Route\RouteRegistryInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Teapot\StatusCode;
use Tuupola\Middleware\JwtAuthentication;

final class ApiHandler
{
    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private SapiEmitter $sapiEmitter;

    private RouteRegistryInterface $routeRegistry;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        SapiEmitter $sapiEmitter,
        RouteRegistryInterface $routeRegistry
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->sapiEmitter     = $sapiEmitter;
        $this->routeRegistry   = $routeRegistry;
    }

    public function handle(
        App $app
    ): void {
        $app->setBasePath('/api/json');
        $app->addBodyParsingMiddleware();

        // error handling setup
        $errorHandler = $app->addErrorMiddleware(
            false,
            true,
            true,
            $this->logger
        )->getDefaultErrorHandler();

        $errorHandler->forceContentType('application/json');
        $errorHandler->registerErrorRenderer(
            'application/json',
            JsonErrorHandler::class
        );

        // jwt auth
        $app->add(
            new JwtAuthentication([
                'secure' => !$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::JWT_INSECURE),
                'logger' => $this->logger,
                'path' => ['/api/json'],
                'ignore' => ['/api/json/v1/session'],
                'secret' => $this->configContainer->getJwtSecret(),
            ])
        );

        $this->routeRegistry->register($app);

        try {
            $app->run();
        } catch (RuntimeException $e) {
            $this->sapiEmitter->emit(
                $this->responseFactory->createResponse(StatusCode::INTERNAL_SERVER_ERROR)
            );
        }
    }
}
