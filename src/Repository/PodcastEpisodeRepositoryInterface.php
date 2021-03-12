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
 */

namespace Ampache\Repository;

use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Generator;

interface PodcastEpisodeRepositoryInterface
{
    /**
     * This returns an array of ids of latest podcast episodes in this catalog
     *
     * @return int[]
     */
    public function getNewestPodcastsIds(
        int $catalogId,
        int $count
    ): array;

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDownloadableEpisodeIds(
        Podcast $podcast,
        int $limit
    ): Generator;

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDeletableEpisodes(
        Podcast $podcast,
        int $limit
    ): Generator;

    public function create(
        int $podcastId,
        string $title,
        string $guid,
        string $source,
        string $website,
        string $description,
        string $author,
        string $category,
        int $time,
        int $pubdate
    ): bool;

    /**
     * Gets all episodes for the podcast
     *
     * @param string $state_filter
     * @return int[]
     */
    public function getEpisodeIds(
        int $podcastId,
        ?string $state_filter = null
    ): array;

    public function remove(Podcast_Episode $podcastEpisode): bool;

    public function changeState(int $podcastEpisodeId, string $state): void;

    /**
     * Sets the vital meta informations after the episode has been downloaded
     */
    public function updateDownloadState(
        Podcast_Episode $podcastEpisode,
        string $filePath,
        int $size,
        int $duration
    ): void;
}
