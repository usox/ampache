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

namespace Ampache\Module\Authorization;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Session;

/**
 * @todo remove
 *
 * This class handles all of the session related stuff in Ampache
 * it takes over for the vauth libs, and takes some stuff out of other
 * classes where it didn't belong.
 */
class Auth
{

    /**
     * logout
     *
     * This is called when you want to log out and nuke your session.
     * This is the function used for the Ajax logouts, if no id is passed
     * it tries to find one from the session,
     * @param string $key
     * @param boolean $relogin
     * @return boolean
     */
    public static function logout($key = '', $relogin = true)
    {
        // If no key is passed try to find the session id
        $key = $key ? $key : session_id();

        // Nuke the cookie before all else
        Session::destroy($key);
        if ((!$relogin) && AmpConfig::get('logout_redirect')) {
            $target = AmpConfig::get('logout_redirect');
        } else {
            $target = AmpConfig::get('web_path') . '/login.php';
        }

        // Do a quick check to see if this is an AJAXed logout request
        // if so use the iframe to redirect
        if (defined('AJAX_INCLUDE')) {
            ob_end_clean();
            ob_start();

            xoutput_headers();

            $results            = array();
            $results['rfc3514'] = '<script>reloadRedirect("' . $target . '")</script>';
            echo (string)xoutput_from_array($results);
        } else {
            /* Redirect them to the login page */
            header('Location: ' . $target);
        }

        return false;
    }
}
