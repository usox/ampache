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
 * along with podcastEpisode program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Log\LoggerInterface;

final class PodcastEpisodeDownloader implements PodcastEpisodeDownloaderInterface
{
    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    private LoggerInterface $logger;

    private ModelFactoryInterface $modelFactory;

    private UtilityFactoryInterface $utilityFactory;

    public function __construct(
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository,
        LoggerInterface $logger,
        ModelFactoryInterface $modelFactory,
        UtilityFactoryInterface $utilityFactory
    ) {
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
        $this->logger                   = $logger;
        $this->modelFactory             = $modelFactory;
        $this->utilityFactory           = $utilityFactory;
    }

    /**
     * Downloads the podcast episode to the catalog
     */
    public function download(Podcast_Episode $podcastEpisode): void
    {
        if (!empty($podcastEpisode->source)) {
            $podcast = $this->modelFactory->createPodcast((int) $podcastEpisode->podcast);
            $file    = $podcast->get_root_path();
            if (!empty($file)) {
                $pinfo = pathinfo($podcastEpisode->source);

                $file .= sprintf(
                    '%s%s-%s-%s',
                    DIRECTORY_SEPARATOR,
                    $podcastEpisode->pubdate,
                    str_replace(['?', '<', '>', '\\', '/'], '_', $podcastEpisode->title),
                    strtok($pinfo['basename'], '?')
                );

                $this->logger->info(
                    sprintf('Downloading %s to %s ...', $podcastEpisode->source, $file),
                    [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                );

                if (file_put_contents($file, fopen($podcastEpisode->source, 'r')) !== false) {
                    $this->logger->info(
                        'Download completed.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                    $vainfo = $this->utilityFactory->createVaInfo($file);
                    $vainfo->get_info();

                    $infos = $vainfo->cleanTagInfo(
                        $vainfo->tags,
                        $vainfo->getTagType($vainfo->tags),
                        $file
                    );

                    // No time information, get it from file
                    if ($podcastEpisode->time < 1) {
                        $time = $infos['time'];
                    } else {
                        $time = $podcastEpisode->time;
                    }

                    $this->podcastEpisodeRepository->updateDownloadState(
                        $podcastEpisode,
                        $file,
                        (int) $infos['size'],
                        (int) $time
                    );
                } else {
                    $this->logger->critical(
                        'Error when downloading podcast episode.',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );
                }
            }
        } else {
            $this->logger->error(
                sprintf('Cannot download podcast episode %d, empty source.', $podcastEpisode->getId()),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
        }
    }
}
