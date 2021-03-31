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

namespace Ampache\Module\Api\Json\Route\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Json\AbstractApiMethod;
use Ampache\Module\Api\Json\ErrorHandling\Exception\ObjectNotFoundException;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides detail of a certain podcast
 *
 * Example: GET /podcast/123
 */
final class GetPodcast extends AbstractApiMethod
{
    private ConfigContainerInterface $configContainer;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->configContainer   = $configContainer;
        $this->podcastRepository = $podcastRepository;
    }

    /**
     * @return mixed
     *
     * @throws FunctionDisabledException
     * @throws ObjectNotFoundException
     */
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments
    ) {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            throw new FunctionDisabledException(T_('Enable: podcast'));
        }

        $podcastId = (int) $arguments['podcastId'];

        $podcast = $this->podcastRepository->findById($podcastId);
        if ($podcast === null) {
            throw new ObjectNotFoundException(
                sprintf('podcast `%d` not found', $podcastId)
            );
        }

        return [
            'title' => $podcast->getTitleFormatted(),
            'description' => $podcast->getDescriptionFormatted(),
            'language' => $podcast->getLanguage(),
            'copyright' => $podcast->getCopyrightFormatted(),
            'feedUrl' => $podcast->getFeed(),
            'generator' => $podcast->getGeneratorFormatted(),
            'website' => $podcast->getWebsiteFormatted(),
            'buildDate' => $podcast->getLastBuildDate(),
            'syncDate' => $podcast->getLastSync(),
            'publicUrl' => $podcast->getLink(),
        ];
    }
}
