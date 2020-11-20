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

declare(strict_types=0);

namespace Ampache\Module\Application\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Model\Shoutbox;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\UiInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class AddShoutAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_shout';

    private UiInterface $ui;

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UiInterface $ui,
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->ui              = $ui;
        $this->responseFactory = $responseFactory;
        $this->configContainer = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Must be at least a user to do this
        if (!Access::check('interface', 25)) {
            $this->ui->showHeader();

            Ui::access_denied();

            $this->ui->showQueryStats();
            $this->ui->showFooter();
            
            return null;
        }

        if (!Core::form_verify('add_shout', 'post')) {
            $this->ui->showHeader();

            Ui::access_denied();

            $this->ui->showQueryStats();
            $this->ui->showFooter();

            return null;
        }

        // Remove unauthorized defined values from here
        if (filter_has_var(INPUT_POST, 'user')) {
            unset($_POST['user']);
        }
        if (filter_has_var(INPUT_POST, 'date')) {
            unset($_POST['date']);
        }

        if (!InterfaceImplementationChecker::is_library_item(Core::get_post('object_type'))) {
            $this->ui->showHeader();

            Ui::access_denied();

            $this->ui->showQueryStats();
            $this->ui->showFooter();
            
            return null;
        }

        Shoutbox::create($_POST);
        
        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/shout.php?action=show_add_shout&type=%s&id=%d',
                    $this->configContainer->getWebPath(),
                    $_POST['object_type'],
                    (int) ($_POST['object_id'])
                )
            );
    }
}
