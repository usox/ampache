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
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;

final class SpotifyCollectorModule implements CollectorModuleInterface
{
    /**
     * This function gathers art from the spotify catalog
     *
     * @param Art $art
     * @param int $limit
     * @param array $data
     *
     * @return array
     */
    public function collect(
        Art $art,
        int $limit = 5,
        array $data = []
    ): array {
        static $accessToken = null;
        $images             = [];
        if (!AmpConfig::get('spotify_client_id') || !AmpConfig::get('spotify_client_secret')) {
            debug_event('art.class', 'gather_spotify: Missing Spotify credentials, check your config', 5);

            return $images;
        }
        $clientId     = AmpConfig::get('spotify_client_id');
        $clientSecret = AmpConfig::get('spotify_client_secret');
        $session      = null;

        if (!isset($accessToken)) {
            try {
                $session = new SpotifySession($clientId, $clientSecret);
                $session->requestCredentialsToken();
                $accessToken = $session->getAccessToken();
            } catch (SpotifyWebAPIException $error) {
                debug_event('art.class', "gather_spotify: A problem exists with the client credentials", 5);
            }
        }
        $api   = new SpotifyWebAPI();
        $types = $art->type . 's';
        $api->setAccessToken($accessToken);
        if ($art->type == 'artist') {
            debug_event('art.class', "gather_spotify artist: " . $data['artist'], 5);
            $query   = $data['artist'];
            $getType = 'getArtist';
        } elseif ($art->type == 'album') {
            debug_event('art.class', "gather_spotify album: " . $data['album'], 5);
            $query   = 'album:' . $data['album'] . ' artist:' . $data['artist'];
            $getType = 'getAlbum';
        } else {
            return $images;
        }

        try {
            $response = $api->search($query, $art->type);
        } catch (SpotifyWebAPIException $error) {
            if ($error->hasExpiredToken()) {
                $accessToken = $session->getAccessToken();
            } elseif ($error->getCode() == 429) {
                $lastResponse = $api->getRequest()->getLastResponse();
                $retryAfter   = $lastResponse['headers']['Retry-After'];
                // Number of seconds to wait before sending another request
                sleep($retryAfter);
            }
            $response = $api->search($query, $art->type);
        } // end of catch

        if (count($response->{$types}->items)) {
            foreach ($response->{$types}->items as $item) {
                $item_id = $item->id;
                $result  = $api->{$getType}($item_id);
                $image   = $result->images[0];
                debug_event('art.class', "gather_spotify: found " . $image->url, 5);
                $images[] = array(
                    'url' => $image->url,
                    'mime' => 'image/jpeg',
                    'title' => 'Spotify'
                );
            }
        }

        return $images;
    }
}
