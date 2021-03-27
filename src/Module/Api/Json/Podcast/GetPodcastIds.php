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

namespace Ampache\Module\Api\Json\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Json\AbstractApiMethod;
use Ampache\Module\Api\Json\ErrorHandling\Exception\SortOrderInvalidException;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provides access to the podcast object list
 *
 * Example: /podcast?sortField=title&sortOrder=DESC&limit=666&offset=42
 */
final class GetPodcastIds extends AbstractApiMethod
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * @return mixed
     *
     * @throws FunctionDisabledException
     * @throws SortOrderInvalidException
     */
    public function handle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $arguments
    ) {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::PODCAST) === false) {
            throw new FunctionDisabledException(T_('Enable: podcast'));
        }
        $queryParams = $request->getQueryParams();

        $sortField = $queryParams['sortField'] ?? 'title';
        $sortOrder = $queryParams['sortOrder'] ?? 'ASC';
        $limit     = (int) ($queryParams['limit'] ?? 25);
        $offset    = (int) ($queryParams['offset'] ?? 0);

        $browse = $this->modelFactory->createBrowse();
        $browse->reset_filters();
        $browse->set_type('podcast');
        $browse->set_start($offset);
        $browse->set_offset($limit);
        $browse->set_is_simple(true);

        if ($browse->set_sort($sortField, $sortOrder) === false) {
            throw new SortOrderInvalidException(
                sprintf('Sort options invalid: [%s,%s]', $sortField, $sortOrder)
            );
        }

        return $browse->get_objects();
    }
}
