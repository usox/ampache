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

namespace Ampache\Module\Api\Json\Route;

use Ampache\MockeryTestCase;
use Slim\App;

class RouteRegistryTest extends MockeryTestCase
{
    private RouteRegistry $subject;

    public function setUp(): void
    {
        $this->subject = new RouteRegistry();
    }

    public function testRegisterRegistersRoutes(): void
    {
        $app = $this->mock(App::class);

        $app->shouldReceive('get')
            ->with(
                '/v1/podcast',
                V1\Podcast\GetPodcastIds::class
            )
            ->once();
        $app->shouldReceive('get')
            ->with(
                '/v1/podcast/{podcastId}',
                V1\Podcast\GetPodcast::class
            )
            ->once();

        $app->shouldReceive('post')
            ->with(
                '/v1/session/login',
                V1\Session\Login::class
            )
            ->once();

        $this->subject->register($app);
    }
}
