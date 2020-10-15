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

use Ampache\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ShowLyricsAction implements ApplicationActionInterface
{
    public function run(
        ServerRequestInterface $request
    ): ?ResponseInterface {
        Ui::show_header();

        $song = new Song($_REQUEST['song_id']);
        $song->format();
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics();
        require_once Ui::find_template('show_lyrics.inc.php');

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();

        return null;
    }

    public static function getRequestKey(): string
    {
        return 'show_lyrics';
    }
}
