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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Podcast\PodcastStateEnum;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;

final class PodcastEpisodeRepository implements PodcastEpisodeRepositoryInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * This returns an array of ids of latest podcast episodes in this catalog
     *
     * @return int[]
     */
    public function getNewestPodcastsIds(
        int $catalogId,
        int $count
    ): array {
        $results = array();

        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ' . 'INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` ' . 'WHERE `podcast`.`catalog` = ? ' . 'ORDER BY `podcast_episode`.`pubdate` DESC';
        if ($count > 0) {
            $sql .= sprintf(' LIMIT %d', $count);
        }
        $db_results = Dba::read($sql, [$catalogId]);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDownloadableEpisodes(
        Podcast $podcast,
        int $limit
    ): iterable {
        $sql = <<<SQL
        SELECT
             `podcast_episode`.`id`
        FROM
             `podcast_episode`
            INNER JOIN 
                `podcast`
            ON
                `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
              `podcast`.`id` = ?
          AND
              `podcast_episode`.`addition_time` > `podcast`.`lastsync`
        ORDER BY
              `podcast_episode`.`pubdate` DESC
        LIMIT %d;
        SQL;

        $db_results = Dba::read(
            sprintf($sql, $limit),
           [$podcast->getId()]
        );
        while ($row = Dba::fetch_row($db_results)) {
            yield new Podcast_Episode((int) $row[0]);
        }
    }

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDeletableEpisodes(
        Podcast $podcast,
        int $limit
    ): iterable {
        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
        WHERE
            `podcast_episode`.`podcast` = ?
        ORDER BY
            `podcast_episode`.`pubdate` DESC
        LIMIT
            %d,18446744073709551615
        SQL;

        $db_results = Dba::read(
            sprintf($sql, $limit),
            [$podcast->getId()]
        );
        while ($row = Dba::fetch_row($db_results)) {
            yield new Podcast_Episode((int) $row[0]);
        }
    }

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
    ): bool {
        $sql = <<<SQL
        INSERT INTO
            `podcast_episode`
            (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $result = Dba::write(
            $sql,
            [
                $title,
                $guid,
                $podcastId,
                'pending',
                $source,
                $website,
                $description,
                $author,
                $category,
                $time,
                $pubdate,
                time()
            ]
        );

        return $result !== false;
    }

    /**
     * Gets all episodes for the podcast
     *
     * @return int[]
     */
    public function getEpisodeIds(
        Podcast $podcast,
        ?string $state_filter = null
    ): array {
        $catalogDisabled = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE);

        $params = [];
        $sql    = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ';
        if ($catalogDisabled) {
            $sql .= 'LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` ';
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast`.`catalog` ';
        }
        $sql .= 'WHERE `podcast_episode`.`podcast`= ? ';
        $params[] = $podcast->getId();
        if ($state_filter !== null) {
            $sql .= 'AND `podcast_episode`.`state` = ? ';
            $params[] = $state_filter;
        }
        if ($catalogDisabled) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
        $sql .= 'ORDER BY `podcast_episode`.`pubdate` DESC';

        $db_results = Dba::read($sql, $params);

        $results = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    public function remove(Podcast_Episode $podcastEpisode): bool
    {
        $result = Dba::write(
            'DELETE FROM `podcast_episode` WHERE `id` = ?',
            [$podcastEpisode->getId()]
        );

        return $result !== false;
    }

    public function changeState(
        Podcast_Episode $podcastEpisode,
        string $state
    ): void {
        Dba::write(
            'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
            [$state, $podcastEpisode->getId()]
        );
    }

    /**
     * Sets the vital meta informations after the episode has been downloaded
     */
    public function updateDownloadState(
        Podcast_Episode $podcastEpisode,
        string $filePath,
        int $size,
        int $duration
    ): void {
        Dba::write(
            'UPDATE `podcast_episode` SET `file` = ?, `size` = ?, `time` = ?, `state` = ? WHERE `id` = ?',
            [
                $filePath,
                $size,
                $duration,
                PodcastStateEnum::COMPLETED,
                $podcastEpisode->getId(),
            ]
        );
    }

    /**
     * Cleans up the podcast_episode table
     */
    public function collectGarbage(): void
    {
        $sql = <<<SQL
        DELETE FROM
            `podcast_episode`
        USING
            `podcast_episode`
        LEFT JOIN
            `podcast`
        ON
            `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`id` IS NULL
        SQL;

        Dba::write($sql);
    }

    /**
     * Returns the amount of available episodes for a certain podcast
     */
    public function getEpisodeCount(int $podcastId): int
    {
        $db_results = Dba::read(
            'SELECT COUNT(`podcast_episode`.`id`) AS `episode_count` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ?',
            [$podcastId]
        );

        return (int) Dba::fetch_assoc($db_results)['episode_count'];
    }
}
