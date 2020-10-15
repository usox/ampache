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
use Ampache\Model\Catalog;
use Ampache\Model\Song;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Module\Util\Ui;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final class ConfirmDeleteAction implements ApplicationActionInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    public function run(
        ServerRequestInterface $request
    ): ?ResponseInterface {
        Ui::show_header();

        $response = null;
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {

            // Show the Footer
            Ui::show_query_stats();
            Ui::show_footer();

            return $response;
        }

        $song = new Song($_REQUEST['song_id']);
        if (!Catalog::can_remove($song)) {
            $this->logger->critical(
                'Unauthorized to remove the song `.' . $song->id . '`.',
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );
            Ui::access_denied();

            // Show the Footer
            Ui::show_query_stats();
            Ui::show_footer();

            return $response;
        }

        if ($song->remove()) {
            show_confirmation(
                T_('No Problem'),
                T_('Song has been deleted'),
                $this->configContainer->getWebPath()
            );
        } else {
            show_confirmation(
                T_("There Was a Problem"),
                T_("Couldn't delete this Song."),
                $this->configContainer->getWebPath()
            );
        }

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();

        return $response;
    }

    public static function getRequestKey(): string
    {
        return 'confirm_delete';
    }
}
