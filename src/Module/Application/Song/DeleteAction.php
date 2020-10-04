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

declare(strict_types=0);

namespace Ampache\Module\Application\Song;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;

final class DeleteAction implements ApplicationActionInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    public function run(): ?ResponseInterface
    {
        Ui::show_header();
        $response = null;
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            // Show the Footer
            Ui::show_query_stats();
            Ui::show_footer();

            return $response;
        }

        $song_id = (string) scrub_in($_REQUEST['song_id']);
        show_confirmation(
            T_('Are You Sure?'),
            T_('The Song will be deleted'),
            sprintf(
                '%s/song.php?action=confirm_delete&song_id=%s',
                $this->configContainer->getWebPath(),
                $song_id
            ),
            1,
            'delete_song'
        );

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();

        return $response;
    }

    public static function getRequestKey(): string
    {
        return 'delete';
    }
}
