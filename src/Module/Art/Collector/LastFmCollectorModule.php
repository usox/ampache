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

use Ampache\Model\Art;
use Ampache\Module\Util\Recommendation;
use Exception;

final class LastFmCollectorModule implements CollectorModuleInterface
{
    /**
     * This returns the art from lastfm. It doesn't currently require an
     * account but may in the future.
     *
     * @param Art $art
     * @param integer $limit
     * @param array $data
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        $images = [];

        try {
            $coverart = array();
            // search for album objects
            if ((!empty($data['artist']) && !empty($data['album']))) {
                $xmldata = Recommendation::album_search($data['artist'], $data['album']);
                if (!$xmldata) {
                    return array();
                }
                if (!$xmldata->album->image) {
                    return array();
                }
                foreach ($xmldata->album->image as $albumart) {
                    $coverart[] = (string)$albumart;
                }
            }
            // Albums only for last FM
            if (empty($coverart)) {
                return array();
            }
            ksort($coverart);
            foreach ($coverart as $url) {
                // We need to check the URL for the /noimage/ stuff
                if (is_array($url) || strpos($url, '/noimage/') !== false) {
                    debug_event('art.class', 'LastFM: Detected as noimage, skipped', 3);
                    continue;
                }
                debug_event('art.class', 'LastFM: found image ' . $url, 3);

                // HACK: we shouldn't rely on the extension to determine file type
                $results  = pathinfo($url[0]);
                $mime     = 'image/' . $results['extension'];
                $images[] = array('url' => $url, 'mime' => $mime, 'title' => 'LastFM');
                if ($limit && count($images) >= $limit) {
                    return $images;
                }
            } // end foreach
        } catch (Exception $error) {
            debug_event('art.class', 'LastFM error: ' . $error->getMessage(), 3);
        }

        return $images;
    }
}
