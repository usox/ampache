<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * This file creates and initializes the central DI-Container
 */
namespace Ampache\Config;

use DI\ContainerBuilder;
use function DI\factory;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    ConfigContainerInterface::class => factory(static function (): ConfigContainerInterface {
        return new ConfigContainer(AmpConfig::get_all());
    }),
]);
$builder->addDefinitions(
    require_once __DIR__ . '/../Application/service_definition.php',
    require_once __DIR__ . '/../Module/Util/service_definition.php',
    require_once __DIR__ . '/../Module/WebDav/service_definition.php',
    require_once __DIR__ . '/../Module/Authentication/service_definition.php',
);

return $builder->build();
