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

interface PodcastRepositoryInterface
{
    /**
     * This returns an array of ids of podcasts in this catalog
     *
     * @return int[]
     */
    public function getPodcastIds(int $catalogId): array;

    public function remove(
        Podcast $podcast
    ): bool;

    public function updateLastsync(
        Podcast $podcast,
        int $time
    ): void;

    public function update(
        int $podcastId,
        string $feed,
        string $title,
        string $website,
        string $description,
        string $generator,
        string $copyright
    ): void;

    public function insert(
        string $feedUrl,
        int $catalogId,
        string $title,
        string $website,
        string $description,
        string $language,
        string $copyright,
        string $generator,
        int $lastBuildDate
    ): ?int;

    /**
     * Looks for existing podcast having a certain feed url to detect duplicated
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?int;
}
