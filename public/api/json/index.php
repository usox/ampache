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

use Ampache\Module\Api\Json\ErrorHandling\JsonErrorHandler;
use Ampache\Module\Api\Json\Middleware\AuthenticationMiddleware;
use Ampache\Module\Api\Json\Podcast\GetPodcast;
use Ampache\Module\Api\Json\Podcast\GetPodcastIds;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

/** @var ContainerInterface $dic */
$dic = require __DIR__ . '/../../../src/Config/Init.php';

$app = AppFactory::create(
    $dic->get(ResponseFactoryInterface::class),
    $dic
);
$app->setBasePath('/api/json');

// error handling setup
$errorHandler = $app->addErrorMiddleware(
    false,
    true,
    true,
    $dic->get(LoggerInterface::class)
)->getDefaultErrorHandler();

$errorHandler->forceContentType('application/json');
$errorHandler->registerErrorRenderer(
    'application/json',
    JsonErrorHandler::class
);

// register routes
$app->group('/podcast', function (RouteCollectorProxy $group) {
    $group->get('/', GetPodcastIds::class);
    $group->get('/{podcastId}', GetPodcast::class);
})->add(new AuthenticationMiddleware());

$app->run();
