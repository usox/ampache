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

namespace Ampache\Module\Application\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'show';

    private ConfigContainerInterface $configContainer;

    private UiInterface $ui;

    private ModelFactoryInterface $modelFactory;

    private PodcastEpisodeRepositoryInterface $podcastEpisodeRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UiInterface $ui,
        ModelFactoryInterface $modelFactory,
        PodcastEpisodeRepositoryInterface $podcastEpisodeRepository
    ) {
        $this->configContainer          = $configContainer;
        $this->ui                       = $ui;
        $this->modelFactory             = $modelFactory;
        $this->podcastEpisodeRepository = $podcastEpisodeRepository;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            return null;
        }
        $podcastId = (int) ($request->getQueryParams()['podcast'] ?? 0);

        $this->ui->showHeader();
        if ($podcastId > 0) {
            $podcast = $this->modelFactory->createPodcast($podcastId);

            $this->ui->show(
                'show_podcast.inc.php',
                [
                    'podcastEpisodeIds' => $this->podcastEpisodeRepository->getEpisodeIds($podcast),
                    'podcast' => $podcast
                ]
            );
        }

        $this->ui->showQueryStats();
        $this->ui->showFooter();

        return null;
    }
}
