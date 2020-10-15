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

namespace Ampache\Module\Application;

use Ampache\Module\System\LegacyLogger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ApplicationRunner
{
    private ContainerInterface $dic;

    private LoggerInterface $logger;

    public function __construct(
        ContainerInterface $dic,
        LoggerInterface $logger
    ) {
        $this->dic    = $dic;
        $this->logger = $logger;
    }

    /**
     * @param array<string, string> $action_list A dict containing request keys and handler class names
     * @param string $default_action The request key for the default action
     */
    public function run(
        ServerRequestInterface $request,
        array $action_list,
        string $default_action
    ): void {
        $action_name = $request->getQueryParams()['action'] ?? '';

        $handler_name = $action_list[$action_name] ?? $action_list[$default_action];

        try {
            /** @var ApplicationActionInterface $handler */
            $handler = $this->dic->get($handler_name);
        } catch (ContainerExceptionInterface $e) {
            $this->logger->critical(
                sprintf('No handler found for action `%s`', $action_name),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return;
        }

        $this->logger->debug(
            sprintf('Found handler `%s` for action `%s`', $handler_name, $action_name),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $response = $handler->run();

        if ($response === null) {
            return;
        }

        // @todo emit psr7 response
    }
}
