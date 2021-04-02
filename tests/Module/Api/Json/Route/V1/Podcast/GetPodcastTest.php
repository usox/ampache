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
use Ampache\Module\Api\Json\ErrorHandling\Exception\ObjectNotFoundException;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastRepositoryInterface;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetPodcastTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface */
    private MockInterface $configContainer;

    /** @var PodcastRepositoryInterface|MockInterface */
    private MockInterface $podcastRepository;

    private GetPodcast $subject;

    public function setUp(): void
    {
        $this->configContainer   = $this->mock(ConfigContainerInterface::class);
        $this->podcastRepository = $this->mock(PodcastRepositoryInterface::class);

        $this->subject = new GetPodcast(
            $this->configContainer,
            $this->podcastRepository
        );
    }

    public function testHandleThrowsExceptionIfDisabled(): void
    {
        $request   = $this->mock(ServerRequestInterface::class);
        $response  = $this->mock(ResponseInterface::class);
        $arguments = [];

        $this->expectException(FunctionDisabledException::class);
        $this->expectExceptionMessage('Enable: podcast');

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnFalse();

        $this->subject->handle(
            $request,
            $response,
            $arguments
        );
    }

    public function testHandleThrowsExceptionIfNotFound(): void
    {
        $request   = $this->mock(ServerRequestInterface::class);
        $response  = $this->mock(ResponseInterface::class);

        $podcastId = 666;
        $arguments = [
            'podcastId' => $podcastId,
        ];

        $this->expectException(ObjectNotFoundException::class);
        $this->expectExceptionMessage(sprintf('podcast `%d` not found', $podcastId));

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturnNull();

        $this->subject->handle(
            $request,
            $response,
            $arguments
        );
    }

    public function testHandleReturnsData(): void
    {
        $request   = $this->mock(ServerRequestInterface::class);
        $response  = $this->mock(ResponseInterface::class);
        $podcast   = $this->mock(PodcastInterface::class);

        $podcastId = 666;
        $arguments = [
            'podcastId' => $podcastId,
        ];
        $title       = 'some-title';
        $description = 'some-description';
        $language    = 'some-language';
        $copyright   = 'some-copyright';
        $feedUrl     = 'some-feedurl';
        $generator   = 'some-generator';
        $website     = 'some-website';
        $buildDate   = 123456;
        $syncDate    = 789001;
        $publicUrl   = 'some-url';

        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::PODCAST)
            ->once()
            ->andReturnTrue();

        $this->podcastRepository->shouldReceive('findById')
            ->with($podcastId)
            ->once()
            ->andReturn($podcast);

        $podcast->shouldReceive('getTitleFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($title);
        $podcast->shouldReceive('getDescriptionFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($description);
        $podcast->shouldReceive('getLanguage')
            ->withNoArgs()
            ->once()
            ->andReturn($language);
        $podcast->shouldReceive('getCopyrightFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($copyright);
        $podcast->shouldReceive('getFeed')
            ->withNoArgs()
            ->once()
            ->andReturn($feedUrl);
        $podcast->shouldReceive('getGeneratorFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($generator);
        $podcast->shouldReceive('getWebsiteFormatted')
            ->withNoArgs()
            ->once()
            ->andReturn($website);
        $podcast->shouldReceive('getLastBuildDate')
            ->withNoArgs()
            ->once()
            ->andReturn($buildDate);
        $podcast->shouldReceive('getLastSync')
            ->withNoArgs()
            ->once()
            ->andReturn($syncDate);
        $podcast->shouldReceive('getLink')
            ->withNoArgs()
            ->once()
            ->andReturn($publicUrl);

        $this->assertSame(
            [
                'title' => $title,
                'description' => $description,
                'language' => $language,
                'copyright' => $copyright,
                'feedUrl' => $feedUrl,
                'generator' => $generator,
                'website' => $website,
                'buildDate' => $buildDate,
                'syncDate' => $syncDate,
                'publicUrl' => $publicUrl,
            ],
            $this->subject->handle(
                $request,
                $response,
                $arguments
            )
        );
    }
}
