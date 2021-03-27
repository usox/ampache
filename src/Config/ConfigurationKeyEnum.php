<?php

declare(strict_types=1);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Config;

/**
 * This class contains constants for all available configuration keys
 */
final class ConfigurationKeyEnum
{
    public const SESSION_NAME                  = 'session_name';
    public const BACKEND_WEBDAV                = 'webdav_backend';
    public const RAW_WEB_PATH                  = 'raw_web_path';
    public const USE_AUTH                      = 'use_auth';
    public const WEB_PATH                      = 'web_path';
    public const ALLOWED_ZIP_TYPES             = 'allow_zip_types';
    public const USER_FLAGS                    = 'userflags';
    public const WAVEFORM                      = 'waveform';
    public const DIRECTPLAY                    = 'directplay';
    public const SOCIABLE                      = 'sociable';
    public const SHARE                         = 'share';
    public const STATISTICAL_GRAPHS            = 'statistical_graphs';
    public const UPLOAD_ALLOW_EDIT             = 'upload_allow_edit';
    public const LABEL                         = 'label';
    public const SHOW_LYRICS                   = 'show_lyrics';
    public const LICENSING                     = 'licensing';
    public const SHOW_SKIPPED_TIMES            = 'show_skipped_times';
    public const SHOW_PLAYED_TIMES             = 'show_played_times';
    public const DEMO_MODE                     = 'demo_mode';
    public const THEME_PATH                    = 'theme_path';
    public const DEBUG_MODE                    = 'debug';
    public const RATINGS                       = 'ratings';
    public const ALBUM_RELEASE_TYPE            = 'album_release_type';
    public const ALLOW_VIDEO                   = 'allow_video';
    public const STATS_THRESHOLD               = 'stats_threshold';
    public const UPLOAD_USER_ARTIST            = 'upload_user_artist';
    public const PODCAST                       = 'podcast';
    public const USE_RSS                       = 'use_rss';
    public const SITE_CHARSET                  = 'site_charset';
    public const ALLOW_DEMOCRATIC_PLAYBACK     = 'allow_democratic_playback';
    public const REFRESH_LIMIT                 = 'refresh_limit';
    public const HOME_NOW_PLAYING              = 'home_now_playing';
    public const LANG                          = 'lang';
    public const SITE_TITLE                    = 'site_title';
    public const VERSION                       = 'version';
    public const ACCESS_CONTROL                = 'access_control';
    public const BROADCAST                     = 'broadcast';
    public const RADIO                         = 'live_stream';
    public const REQUIRE_SESSION               = 'require_session';
    public const RESIZE_IMAGES                 = 'resize_images';
    public const CHANNEL                       = 'channel';
    public const ALLOW_UPLOAD                  = 'allow_upload';
    public const USE_NOW_PLAYING_EMBEDDED      = 'use_now_playing_embedded';
    public const NOW_PLAYING_CSS_FILE          = 'now_playing_css_file';
    public const NOW_PLAYING_REFRESH_LIMIT     = 'now_playing_refresh_limit';
    public const PLAY_TYPE                     = 'play_type';
    public const PLAYLIST_TYPE                 = 'playlist_type';
    public const PLAYLIST_METHOD               = 'playlist_method';
    public const ALLOW_PUBLIC_REGISTRATION     = 'allow_public_registration';
    public const CAPTCHA_PUBLIC_REG            = 'captcha_public_reg';
    public const AUTO_USER                     = 'auto_user';
    public const ADMIN_ENABLE_REQUIRED         = 'admin_enable_required';
    public const USER_NO_EMAIL_CONFIRM         = 'user_no_email_confirm';
    public const USER_AGREEMENT                = 'user_agreement';
    public const REGISTRATION_MANDATORY_FIELDS = 'registration_mandatory_fields';
    public const AUTH_METHODS                  = 'auth_methods';
    public const AUTO_CREATE                   = 'auto_create';
    public const AUTH_PASSWORD_SAVE            = 'auth_password_save';
    public const TRACK_USER_IP                 = 'track_user_ip';
    public const EXTERNAL_AUTO_UPDATE          = 'external_auto_update';
    public const AUTOUPDATE                    = 'autoupdate';
    public const ALLOW_LOCALPLAY_PLAYBACK      = 'allow_localplay_playback';
    public const LOCALPLAY_CONTROLLER          = 'localplay_controller';
    public const CATALOG_DISABLE               = 'catalog_disable';
    public const ALBUM_ART_MIN_WIDTH           = 'album_art_min_width';
    public const ALBUM_ART_MAX_WIDTH           = 'album_art_max_width';
    public const ALBUM_ART_MIN_HEIGHT          = 'album_art_min_height';
    public const ALBUM_ART_MAX_HEIGHT          = 'album_art_max_height';
    public const DOWNLOAD                      = 'download';
    public const ALBUM_ART_PREFERRED_FILENAME  = 'album_art_preferred_filename';
    public const SIMPLE_USER_MODE              = 'simple_user_mode';
    public const LOCALPLAY_LEVEL               = 'localplay_level';
    public const ALLOW_ZIP_DOWNLOAD            = 'allow_zip_download';
    public const ENABLE_CUSTOM_METADATA        = 'enable_custom_metadata';
    public const WRITE_ID3                     = 'write_id3';
    public const ALBUM_GROUP                   = 'album_group';
    public const POPULAR_THRESHOLD             = 'popular_threshold';
    public const STREAM_BEAUTIFUL_URL          = 'stream_beautiful_url';
    public const SESSION_LENGTH                = 'session_length';
    public const MEMORY_CACHE                  = 'memory_cache';
    public const MEMORY_CACHE_ADAPTER          = 'memory_cache_adapter';
    public const WANTED_AUTO_ACCEPT            = 'wanted_auto_accept';
    public const UNIQUE_PLAYLIST               = 'unique_playlist';
    public const COMPOSER_BINARY_PATH          = 'composer_binary_path';
    public const DELETE_FROM_DISK              = 'delete_from_disk';
    public const UPLOAD_ALLOW_REMOVE           = 'upload_allow_remove';
    public const HIDE_SEARCH                   = 'hide_search';
    public const SHARE_EXPIRE                  = 'share_expire';
    public const LIVE_STREAM                   = 'live_stream';
    public const AJAX_LOAD                     = 'ajax_load';
    public const PODCAST_NEW_DOWNLOAD          = 'podcast_new_download';
    public const PODCAST_KEEP                  = 'podcast_keep';
    public const ART_ZIP_ADD                   = 'art_zip_add';
    public const FILE_ZIP_COMMENT              = 'file_zip_comment';
    public const OFFSET_LIMIT                  = 'offset_limit';
}
