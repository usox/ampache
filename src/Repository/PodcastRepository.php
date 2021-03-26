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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Podcast;

final class PodcastRepository implements PodcastRepositoryInterface
{
    /**
     * This returns an array of ids of podcasts in this catalog
     *
     * @return int[]
     */
    public function getPodcastIds(
        int $catalogId
    ): array {
        $results = array();

        $db_results = Dba::read(
            'SELECT `podcast`.`id` FROM `podcast` WHERE `podcast`.`catalog` = ?',
            [$catalogId]
        );
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    public function remove(
        Podcast $podcast
    ): bool {
        $result = Dba::write(
            'DELETE FROM `podcast` WHERE `id` = ?',
            [$podcast->getId()]
        );

        return $result !== false;
    }

    public function updateLastsync(
        Podcast $podcast,
        int $time
    ): void {
        Dba::write(
            'UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?',
            [$time, $podcast->getId()]
        );
    }

    public function update(
        int $podcastId,
        string $feed,
        string $title,
        string $website,
        string $description,
        string $generator,
        string $copyright
    ): void {
        Dba::write(
            'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?',
            [$feed, $title, $website, $description, $generator, $copyright, $podcastId]
        );
    }

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
    ): ?int {
        $sql = <<<SQL
        INSERT INTO
            `podcast`
        (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $result = Dba::write(
            $sql,
            [
                $feedUrl,
                $catalogId,
                $title,
                $website,
                $description,
                $language,
                $copyright,
                $generator,
                $lastBuildDate
            ]
        );

        if (!$result) {
            return null;
        }

        return (int) Dba::insert_id();
    }

    /**
     * Looks for existing podcast having a certain feed url to detect duplicated
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?int {
        $db_results = Dba::read(
            "SELECT `id` FROM `podcast` WHERE `feed`= ?",
            [$feedUrl]
        );

        $podcastId = Dba::fetch_assoc($db_results)['id'] ?? null;

        if ($podcastId === null) {
            return null;
        }

        return (int) $podcastId;
    }
}
