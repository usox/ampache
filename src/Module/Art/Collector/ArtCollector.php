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

declare(strict_types=0);

namespace Ampache\Module\Art\Collector;

use Ampache\Config\AmpConfig;
use Ampache\Model\Art;
use Ampache\Model\Plugin;
use Ampache\Module\System\Core;
use Psr\Container\ContainerInterface;

final class ArtCollector implements ArtCollectorInterface
{
    private ContainerInterface $dic;

    public function __construct(
        ContainerInterface $dic
    ) {
        $this->dic = $dic;
    }

    /**
     * This tries to get the art in question
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function collect(
        Art $art,
        array $options = [],
        int $limit = 0
    ): array {
        // Define vars
        $results = [];

        $type = $options['type'] ?? $art->type;

        if (count($options) == 0) {
            debug_event('art.class', 'No options for art search, skipped.', 3);

            return array();
        }
        $config  = AmpConfig::get('art_order');

        /* If it's not set */
        if (empty($config)) {
            // They don't want art!
            debug_event('art.class', 'art_order is empty, skipping art gathering', 3);

            return array();
        } elseif (!is_array($config)) {
            $config = array($config);
        }

        debug_event('art.class', 'Searching using:' . json_encode($config), 3);

        $plugin_names = Plugin::get_plugins('gather_arts');
        foreach ($config as $method) {
            $data = array();
            if (in_array($method, $plugin_names)) {
                $plugin            = new Plugin($method);
                $installed_version = Plugin::get_plugin_version($plugin->_plugin->name);
                if ($installed_version) {
                    if ($plugin->load(Core::get_global('user'))) {
                        $data = $plugin->_plugin->gather_arts($type, $options, $limit);
                    }
                }
            } else {
                $handlerClassName = ArtCollectorTypeEnum::TYPE_CLASS_MAP[$method] ?? null;
                if ($handlerClassName !== null) {
                    debug_event('art.class', "Method used: $method", 4);
                    /** @var CollectorModuleInterface $handler */
                    $handler = $this->dic->get($handlerClassName);

                    $data = $handler->collect(
                        $art,
                        $limit,
                        $options
                    );
                } else {
                    debug_event('art.class', $method . " not defined", 1);
                }
            }

            // Add the results we got to the current set
            $results = array_merge($results, (array)$data);

            if ($limit && count($results) >= $limit) {
                debug_event('art.class', 'results:' . json_encode($results), 3);

                return array_slice($results, 0, $limit);
            }
        }

        return $results;
    }
}
