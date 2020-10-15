<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Gui\Song;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\MockeryTestCase;
use Ampache\Model\ModelFactoryInterface;
use Ampache\Model\Song;
use Mockery\MockInterface;

class SongViewAdapterTest extends MockeryTestCase
{
    /** @var ConfigContainerInterface|MockInterface|null */
    private MockInterface $configContainer;

    /** @var ModelFactoryInterface|MockInterface|null */
    private MockInterface $modelFactory;

    /** @var Song|MockInterface|null */
    private MockInterface $song;
    
    /** @var SongViewAdapter|null */
    private SongViewAdapter $subject;
    
    public function setUp(): void
    {
        $this->configContainer = $this->mock(ConfigContainerInterface::class);
        $this->modelFactory    = $this->mock(ModelFactoryInterface::class);
        $this->song            = $this->mock(Song::class);
        
        $this->subject = new SongViewAdapter(
            $this->configContainer,
            $this->modelFactory,
            $this->song
        );
    }
    
    public function testGetIdReturnsSongId(): void
    {
        $id = 666;
        
        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($id);
        
        $this->assertSame(
            $id,
            $this->song->getId()
        );
    }
    
    public function testIsUserFlagsEnabledReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::USER_FLAGS)
            ->once()
            ->andReturnTrue();
        
        $this->assertTrue(
            $this->subject->isUserFlagsEnabled()
        );
    }
    
    public function testIsWaveformEnabledReturnsValue(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::WAVEFORM)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isWaveformEnabled()
        );
    }

    public function testIsDirectplayEnabled(): void
    {
        $this->configContainer->shouldReceive('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::DIRECTPLAY)
            ->once()
            ->andReturnTrue();

        $this->assertTrue(
            $this->subject->isDirectplayEnabled()
        );
    }
    
    public function testGetWaveformUrlReturnsUrl(): void
    {
        $songId  = 666;
        $webPath = 'some-path';
        
        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);
        
        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/waveform.php?song_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getWaveformUrl()
        );
    }

    public function testGetDisplayStatsUrl(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/stats.php?action=graph&object_type=song&object_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getDisplayStatsUrl()
        );
    }
    
    public function testGetEditButtonTitleReturnsValue(): void
    {
        $this->assertSame(
            'Song Edit',
            $this->subject->getEditButtonTitle()
        );
    }
    
    public function testGetDeletionUrlReturnsValue(): void
    {
        $songId  = 666;
        $webPath = 'some-path';

        $this->song->shouldReceive('getId')
            ->withNoArgs()
            ->once()
            ->andReturn($songId);

        $this->configContainer->shouldReceive('getWebPath')
            ->withNoArgs()
            ->once()
            ->andReturn($webPath);

        $this->assertSame(
            sprintf(
                '%s/song.php?action=delete&song_id=%d',
                $webPath,
                $songId
            ),
            $this->subject->getDeletionUrl()
        );
    }
}
