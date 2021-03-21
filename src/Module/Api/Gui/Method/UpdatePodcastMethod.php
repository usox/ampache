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

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UpdatePodcastMethod implements MethodInterface
{
    public const ACTION = 'update_podcast';

    private ModelFactoryInterface $modelFactory;

    private StreamFactoryInterface $streamFactory;

    private PodcastSyncerInterface $podcastSyncer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        StreamFactoryInterface $streamFactory,
        PodcastSyncerInterface $podcastSyncer
    ) {
        $this->modelFactory  = $modelFactory;
        $this->streamFactory = $streamFactory;
        $this->podcastSyncer = $podcastSyncer;
    }

    /**
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of podcast
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $objectId = $input['filter'] ?? null;

        if ($objectId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_CONTENT_MANAGER) === false) {
            throw new AccessDeniedException(
                T_('Require: 50')
            );
        }

        $podcast = $this->modelFactory->createPodcast((int) $objectId);

        if ($podcast->isNew()) {
            throw new ResultEmptyException(
                sprintf(T_('Not Found: %d'), $objectId)
            );
        }
        if ($this->podcastSyncer->sync($podcast, true)) {
            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(sprintf(T_('Synced episodes for podcast: %d'), $objectId))
                )
            );
        } else {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %d'), $objectId)
            );
        }
    }
}
