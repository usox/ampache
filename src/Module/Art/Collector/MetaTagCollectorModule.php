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

use Ampache\Model\Album;
use Ampache\Model\Art;
use Ampache\Model\Song;
use Ampache\Model\Video;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Exception;
use getID3;

final class MetaTagCollectorModule implements CollectorModuleInterface
{

    /**
     * This looks for the art in the meta-tags of the file
     * itself
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
    ):array {
        if (!$limit) {
            $limit = 5;
        }

        if ($art->type == "video") {
            $data = $this->gather_video_tags($art);
        } elseif ($art->type == 'album') {
            $data = $this->gather_song_tags($art, $limit);
        } else {
            $data = array();
        }

        return $data;
    }

    /**
     * Gather tags from video files.
     */
    public function gather_video_tags(Art $art): array
    {
        $video = new Video($art->uid);

        return $this->gather_media_tags($video);
    }

    /**
     * Gather tags from audio files.
     * @param integer $limit
     * @return array
     */
    public function gather_song_tags(Art $art, int $limit = 5): array
    {
        // We need the filenames
        $album = new Album($art->uid);

        // grab the songs and define our results
        $songs = $album->get_songs();
        $data  = array();

        // Foreach songs in this album
        foreach ($songs as $song_id) {
            $song = new Song($song_id);
            $data = array_merge($data, $this->gather_media_tags($song));

            if ($limit && count($data) >= $limit) {
                return array_slice($data, 0, $limit);
            }
        }

        return $data;
    }

    /**
     * Gather tags from files.
     * @param Song|Video $media
     * @return array
     */
    protected function gather_media_tags($media)
    {
        $mtype  = ObjectTypeToClassNameMapper::reverseMap(get_class($media));
        $data   = array();
        $getID3 = new getID3();
        try {
            $id3 = $getID3->analyze($media->file);
        } catch (Exception $error) {
            debug_event('art.class', 'getid3' . $error->getMessage(), 1);
        }

        if (isset($id3['asf']['extended_content_description_object']['content_descriptors']['13'])) {
            $image  = $id3['asf']['extended_content_description_object']['content_descriptors']['13'];
            $data[] = array(
                $mtype => $media->file,
                'raw' => $image['data'],
                'mime' => $image['mime'],
                'title' => 'ID3'
            );
        }

        if (isset($id3['id3v2']['APIC'])) {
            // Foreach in case they have more then one
            foreach ($id3['id3v2']['APIC'] as $image) {
                $data[] = array(
                    $mtype => $media->file,
                    'raw' => $image['data'],
                    'mime' => $image['mime'],
                    'title' => 'ID3'
                );
            }
        }

        if (isset($id3['comments']['picture']['0'])) {
            $image  = $id3['comments']['picture']['0'];
            $data[] = array(
                $mtype => $media->file,
                'raw' => $image['data'],
                'mime' => $image['image_mime'],
                'title' => 'ID3'
            );

            return $data;
        }

        return $data;
    }
}