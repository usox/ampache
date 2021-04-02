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

namespace Ampache\Config;

/**
 * The ConfigContainer is a containment for all of ampaches configuration data
 */
interface ConfigContainerInterface
{
    /**
     * Replaces the internal config container
     */
    public function updateConfig(array $configuration): ConfigContainerInterface;

    /**
     * Compatibility accessor for direct access to the config array
     */
    public function get(string $configKey);

    /**
     * Returns the name of the PHP session
     */
    public function getSessionName(): string;

    /**
     * Returns the length of the PHP session in seconds
     */
    public function getSessionLength(): int;

    /**
     * Returns the webdav config state
     */
    public function isWebDavBackendEnabled(): bool;

    /**
     * Returns the authentication config state
     */
    public function isAuthenticationEnabled(): bool;

    /**
     * Returns the raw web path
     */
    public function getRawWebPath(): string;

    /**
     * Returns the web path
     */
    public function getWebPath(): string;

    /**
     * Return a list of types which are zip-able
     */
    public function getTypesAllowedForZip(): array;

    /**
     * Return the path to the composer binary
     */
    public function getComposerBinaryPath(): string;

    /**
     * Check if a certain feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool;

    /**
     * Returns the path to the files of the selected theme
     */
    public function getThemePath(): string;

    /**
     * Returns the debug mode state
     */
    public function isDebugMode(): bool;

    /**
     * Returns the demo mode state
     */
    public function isDemoMode(): bool;

    /**
     * Returns the path to the ampache config file
     */
    public function getConfigFilePath(): string;

    /**
     * Returns the amount if items displayed in `popular` lists
     */
    public function getPopularThreshold(int $default): int;

    /**
     * Returns the access level needed for localplay control
     */
    public function getLocalplayLevel(): int;

    /**
     * Returns the secret used to cypher jwt
     */
    public function getJwtSecret(): string;

    /**
     * Returns the validity period of an issued jwt
     */
    public function getJwtTimeout(): int;

    /**
     * @return array{"host": string, "port": int, "user": string, "pass": string}
     */
    public function getProxyOptions(): array;
}
