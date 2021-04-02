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

namespace Ampache\Module\Api\Json\Route\V1\Podcast;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Json\ErrorHandling\Exception\SortOrderInvalidException;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Query;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetPodcastIdsTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface */
    private MockInterface $modelFactory;

    private GetPodcastIds $subject;

    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);

        $this->subject = new GetPodcastIds(
            $this->configContainer,
            $this->modelFactory
        );
    }

    public function testHandleThrowsExceptionIfNotEnabled(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: podcast');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $request,
            $response,
            []
        );
    }

    public function testHandleThrowsExceptionIfSortInvalid(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $browse   = $this->mock(Browse::class);

        $this->expectException(SortOrderInvalidException::class);
        $this->expectExceptionMessage('Sort options invalid: [title,ASC]');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([]);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with('podcast')
            ->once();
        $browse->shouldReceive('set_start')
            ->with(0)
            ->once();
        $browse->shouldReceive('set_offset')
            ->with(Query::DEFAULT_LIMIT)
            ->once();
        $browse->shouldReceive('set_is_simple')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with('title', 'ASC')
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $request,
            $response,
            []
        );
    }

    public function testHandleReturnsData(): void
    {
        $request  = $this->mock(ServerRequestInterface::class);
        $response = $this->mock(ResponseInterface::class);
        $browse   = $this->mock(Browse::class);

        $field     = 'some-field';
        $order     = 'some-order';
        $limit     = 666;
        $offset    = 42;
        $podcastId = 33;

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $request->shouldReceive('getQueryParams')
            ->withNoArgs()
            ->once()
            ->andReturn([
                'sortField' => $field,
                'sortOrder' => $order,
                'limit' => (string) $limit,
                'offset' => (string) $offset,
            ]);

        $this->modelFactory->shouldReceive('createBrowse')
            ->withNoArgs()
            ->once()
            ->andReturn($browse);

        $browse->shouldReceive('reset_filters')
            ->withNoArgs()
            ->once();
        $browse->shouldReceive('set_type')
            ->with('podcast')
            ->once();
        $browse->shouldReceive('set_start')
            ->with($offset)
            ->once();
        $browse->shouldReceive('set_offset')
            ->with($limit)
            ->once();
        $browse->shouldReceive('set_is_simple')
            ->with(true)
            ->once();
        $browse->shouldReceive('set_sort')
            ->with($field, $order)
            ->once()
            ->andReturnTrue();
        $browse->shouldReceive('get_objects')
            ->withNoArgs()
            ->once()
            ->andReturn([(string) $podcastId]);

        $this->assertSame(
            [
                [
                    'id' => $podcastId,
                    'cacheKey' => null,
                ]
            ],
            $this->subject->handle(
                $request,
                $response,
                []
            )
        );
    }
}
