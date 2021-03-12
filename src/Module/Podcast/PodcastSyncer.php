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

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * Syncs and creates new podcast episodes from the feed url
 */
final class PodcastSyncer implements PodcastSyncerInterface
{
    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private PodcastEpisodeCreatorInterface $podcastEpisodeCreator;

    private PodcastRepositoryInterface $podcastRepository;

    private PodcastEpisodeDeleterInterface $podcastEpisodeDeleter;

    private PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader;

    public function __construct(
        LoggerInterface $logger,
        ConfigContainerInterface $configContainer,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        PodcastEpisodeCreatorInterface $podcastEpisodeCreator,
        PodcastRepositoryInterface $podcastRepository,
        PodcastEpisodeDeleterInterface $podcastEpisodeDeleter,
        PodcastEpisodeDownloaderInterface $podcastEpisodeDownloader
    ) {
        $this->logger                   = $logger;
        $this->configContainer          = $configContainer;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->podcastEpisodeCreator    = $podcastEpisodeCreator;
        $this->podcastRepository        = $podcastRepository;
        $this->podcastEpisodeDeleter    = $podcastEpisodeDeleter;
        $this->podcastEpisodeDownloader = $podcastEpisodeDownloader;
    }

    public function sync(
        Podcast $podcast,
        $gather = false
    ): bool {
        $this->logger->info(
            sprintf('Syncing feed %s ...', $podcast->feed),
            [LegacyLogger::CONTEXT_TYPE => __CLASS__]
        );

        $xmlstr = file_get_contents($podcast->feed, false, stream_context_create(Core::requests_options()));
        if ($xmlstr === false) {
            $this->logger->critical(
                sprintf('Cannot access feed %s', $podcast->feed),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }
        $xml = simplexml_load_string($xmlstr);
        if ($xml === false) {
            $this->logger->critical(
                sprintf('Cannot read feed %s', $podcast->feed),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            return false;
        }

        $this->addEpisodes($podcast, $xml->channel->item, (int) $podcast->lastsync, $gather);

        return true;
    }

    public function addEpisodes(
        Podcast $podcast,
        SimpleXMLElement $episodes,
        int $afterdate = 0,
        bool $gather = false
    ): void {
        foreach ($episodes as $episode) {
            $this->podcastEpisodeCreator->create(
                $podcast,
                $episode,
                $afterdate
            );
        }

        // Select episodes to download
        $episode_download_amount = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_NEW_DOWNLOAD);
        if ($episode_download_amount <> 0) {
            $episodes = $this->podcastEpisodeRepository->getDownloadableEpisodeIds(
                $podcast,
                $episode_download_amount
            );

            foreach ($episodes as $episode) {
                $this->podcastEpisodeRepository->changeState($episode->getId(), PodcastStateEnum::PENDING);
                if ($gather) {
                    $this->podcastEpisodeDownloader->download($episode);
                }
            }
        }

        // Remove items outside limit
        $episode_keep_amount = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_KEEP);
        if ($episode_keep_amount > 0) {
            $episodes = $this->podcastEpisodeRepository->getDeletableEpisodes(
                $podcast,
                $episode_keep_amount
            );

            foreach ($episodes as $episode) {
                $this->podcastEpisodeDeleter->delete($episode);
            }
        }

        $this->podcastRepository->updateLastsync($podcast, time());
    }
}
