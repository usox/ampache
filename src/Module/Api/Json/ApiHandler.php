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
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Teapot\StatusCode;
use Tuupola\Middleware\JwtAuthentication;

final class ApiHandler
{
    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    private ContainerInterface $dic;

    private SapiEmitter $sapiEmitter;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger,
        SapiEmitter $sapiEmitter,
        ContainerInterface $dic
    ) {
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
        $this->sapiEmitter     = $sapiEmitter;
        $this->dic             = $dic;
    }

    public function handle(): void
    {
        $app = AppFactory::create(
            $this->responseFactory,
            $this->dic
        );
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
        $app->add(new JwtAuthentication([
            'secure' => $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::JWT_INSECURE) === false,
            'logger' => $this->logger,
            'path' => ['/api/json'],
            'ignore' => ['/api/json/v1/session'],
            'secret' => $this->configContainer->getJwtSecret(),
        ]));

        // register routes
        $app->group('/v1', function (RouteCollectorProxy $group) {
            $group->get('/podcast', Route\Podcast\GetPodcastIds::class);
            $group->get('/podcast/{podcastId}', Route\Podcast\GetPodcast::class);

            $group->post('/session/login', Route\Session\Login::class);
        });

        try {
            $app->run();
        } catch (RuntimeException $e) {
            $this->sapiEmitter->emit(
                $this->responseFactory->createResponse(StatusCode::INTERNAL_SERVER_ERROR)
            );
        }
    }
}
