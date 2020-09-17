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
use Ampache\Model\Album;
use Ampache\Model\Art;
use Ampache\Model\Artist;
use Ampache\Model\Song;
use Ampache\Model\Video;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;

final class FolderCollectorModule implements CollectorModuleInterface
{
    /**
     * This returns the art from the folder of the files
     * If a limit is passed or the preferred filename is found the current
     * results set is returned
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
        if (!$limit) {
            $limit = 5;
        }

        $results   = array();
        $preferred = array();
        // For storing which directories we've already done
        $processed = array();

        /* See if we are looking for a specific filename */
        $preferred_filename = (AmpConfig::get('album_art_preferred_filename')) ?: 'folder.jpg';
        $artist_filename    = AmpConfig::get('artist_art_preferred_filename');
        $artist_art_folder  = AmpConfig::get('artist_art_folder');

        // Array of valid extensions
        $image_extensions = array(
            'bmp',
            'gif',
            'jp2',
            'jpeg',
            'jpg',
            'png'
        );

        $dirs = array();
        if ($art->type == 'album') {
            $media = new Album($art->uid);
            $songs = $media->get_songs();
            foreach ($songs as $song_id) {
                $song   = new Song($song_id);
                $dirs[] = Core::conv_lc_file(dirname($song->file));
            }
        } elseif ($art->type == 'video') {
            $media  = new Video($art->uid);
            $dirs[] = Core::conv_lc_file(dirname($media->file));
        } elseif ($art->type == 'artist') {
            $media = new Artist($art->uid);
            $media->format();
            $preferred_filename = str_replace(array('<', '>', '\\', '/'), '_', $media->f_full_name);
            if ($artist_art_folder) {
                $dirs[] = Core::conv_lc_file($artist_art_folder);
            }
            // get the folders from songs as well
            $songs = $media->get_songs();
            foreach ($songs as $song_id) {
                $song = new Song($song_id);
                // look in the directory name of the files (e.g. /mnt/Music/%artistName%/%album%)
                $dirs[] = Core::conv_lc_file(dirname($song->file, 1));
                // look one level up (e.g. /mnt/Music/%artistName%)
                $dirs[] = Core::conv_lc_file(dirname($song->file, 2));
            }
        }

        foreach ($dirs as $dir) {
            if (isset($processed[$dir])) {
                continue;
            }

            debug_event('art.class', "gather_folder: Opening $dir and checking for " . $art->type . " Art", 3);

            /* Open up the directory */
            $handle = opendir($dir);

            if (!$handle) {
                AmpError::add('general', T_('Unable to open') . ' ' . $dir);
                debug_event('art.class', "gather_folder: Error: Unable to open $dir for album art read", 2);
                continue;
            }

            $processed[$dir] = true;

            // Recurse through this dir and create the files array
            while (false !== ($file = readdir($handle))) {
                $extension = pathinfo($file);
                $extension = $extension['extension'];

                // Make sure it looks like an image file
                if (!in_array($extension, $image_extensions)) {
                    continue;
                }

                $full_filename = $dir . '/' . $file;

                // Make sure it's got something in it
                if (!Core::get_filesize($full_filename)) {
                    debug_event('art.class', "gather_folder: Empty file, rejecting" . $file, 5);
                    continue;
                }

                // Regularize for mime type
                if ($extension == 'jpg') {
                    $extension = 'jpeg';
                }

                // Take an md5sum so we don't show duplicate files.
                $index = md5($full_filename);

                if (
                    (
                        $file == $preferred_filename ||
                        pathinfo($file, PATHINFO_FILENAME) == $preferred_filename) ||
                        (
                            $file == $artist_filename ||
                            pathinfo($file, PATHINFO_FILENAME) == $artist_filename
                        )
                ) {
                    // We found the preferred filename and so we're done.
                    debug_event('art.class', "gather_folder: Found preferred image file: $file", 5);
                    $preferred[$index] = array(
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    );
                    break;
                }
                if ($art->type !== 'artist') {
                    debug_event('art.class', "gather_folder: Found image file: $file", 5);
                    $results[$index] = array(
                        'file' => $full_filename,
                        'mime' => 'image/' . $extension,
                        'title' => 'Folder'
                    );
                }
            } // end while reading dir
            closedir($handle);
        } // end foreach dirs

        if (!empty($preferred)) {
            // We found our favorite filename somewhere, so we need
            // to dump the other, less sexy ones.
            $results = $preferred;
        }

        //debug_event('art.class', "gather_folder: Results: " . json_encode($results), 5);
        if ($limit && count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
        }

        return array_values($results);
    }
}
