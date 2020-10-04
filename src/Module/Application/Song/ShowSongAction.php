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
use Ampache\Gui\Song\SongViewAdapter;
use Ampache\Gui\Song\SongViewInterface;
use Ampache\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ShowSongAction implements ApplicationActionInterface
{
    private SongViewInterface $songView;

    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        SongViewInterface $songView,
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->songView        = $songView;
        $this->logger          = $logger;
        $this->configContainer = $configContainer;
    }

    public function run(): ?ResponseInterface
    {
        Ui::show_header();
        $song = new Song($_REQUEST['song_id']);
        $song->format();
        $song->fill_ext_info();
        if (!$song->id) {
            $this->logger->warning(
                'Requested a song that does not exist',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            echo T_("You have requested a Song that does not exist.");
        } else {
            Ui::show_box_top($song->title . ' ' . T_('Details'), 'box box_song_details');
            $code = $this->songView->render(
                new SongViewAdapter(
                    $this->configContainer,
                    $song
                )
            );
            echo $code;
            Ui::show_box_bottom();
        }
        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();

        return null;
    }

    public static function getRequestKey(): string
    {
        return 'show_song';
    }
}
