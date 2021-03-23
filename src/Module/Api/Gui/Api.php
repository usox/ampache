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

namespace Ampache\Module\Api\Gui;

use Ampache\Repository\Model\Browse;

/**
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 */
class Api
{
    /**
     * This dict contains all known api-methods (key) and their respective handler (value)
     */
    public const METHOD_LIST = [
        Method\HandshakeMethod::ACTION => Method\HandshakeMethod::class,
        Method\PingMethod::ACTION => Method\PingMethod::class,
        Method\GoodbyeMethod::ACTION => Method\GoodbyeMethod::class,
        Method\UrlToSongMethod::ACTION => Method\UrlToSongMethod::class,
        Method\GetIndexesMethod::ACTION => Method\GetIndexesMethod::class,
        Method\GetBookmarkMethod::ACTION => Method\GetBookmarkMethod::class,
        Method\GetSimilarMethod::ACTION => Method\GetSimilarMethod::class,
        Method\AdvancedSearchMethod::ACTION => Method\AdvancedSearchMethod::class,
        Method\ArtistsMethod::ACTION => Method\ArtistsMethod::class,
        Method\ArtistMethod::ACTION => Method\ArtistMethod::class,
        Method\ArtistAlbumsMethod::ACTION => Method\ArtistAlbumsMethod::class,
        Method\ArtistSongsMethod::ACTION => Method\ArtistSongsMethod::class,
        Method\AlbumsMethod::ACTION => Method\AlbumsMethod::class,
        Method\AlbumMethod::ACTION => Method\AlbumMethod::class,
        Method\AlbumSongsMethod::ACTION => Method\AlbumSongsMethod::class,
        Method\LicensesMethod::ACTION => Method\LicensesMethod::class,
        Method\LicenseMethod::ACTION => Method\LicenseMethod::class,
        Method\LicenseSongsMethod::ACTION => Method\LicenseSongsMethod::class,
        Method\GenresMethod::ACTION => Method\GenresMethod::class,
        Method\GenreMethod::ACTION => Method\GenreMethod::class,
        Method\GenreArtistsMethod::ACTION => Method\GenreArtistsMethod::class,
        Method\GenreAlbumsMethod::ACTION => Method\GenreAlbumsMethod::class,
        Method\GenreSongsMethod::ACTION => Method\GenreSongsMethod::class,
        Method\LabelsMethod::ACTION => Method\LabelsMethod::class,
        Method\LabelMethod::ACTION => Method\LabelMethod::class,
        Method\LabelArtistsMethod::ACTION => Method\LabelArtistsMethod::class,
        Method\SongsMethod::ACTION => Method\SongsMethod::class,
        Method\SongMethod::ACTION => Method\SongMethod::class,
        Method\SongDeleteMethod::ACTION => Method\SongDeleteMethod::class,
        Method\PlaylistsMethod::ACTION => Method\PlaylistsMethod::class,
        Method\PlaylistMethod::ACTION => Method\PlaylistMethod::class,
        Method\PlaylistSongsMethod::ACTION => Method\PlaylistSongsMethod::class,
        Method\PlaylistCreateMethod::ACTION => Method\PlaylistCreateMethod::class,
        Method\PlaylistEditMethod::ACTION => Method\PlaylistEditMethod::class,
        Method\PlaylistDeleteMethod::ACTION => Method\PlaylistDeleteMethod::class,
        Method\PlaylistAddSongMethod::ACTION => Method\PlaylistAddSongMethod::class,
        Method\PlaylistRemoveSongMethod::ACTION => Method\PlaylistRemoveSongMethod::class,
        Method\PlaylistGenerateMethod::ACTION => Method\PlaylistGenerateMethod::class,
        Method\SearchSongsMethod::ACTION => Method\SearchSongsMethod::class,
        Method\SharesMethod::ACTION => Method\SharesMethod::class,
        Method\ShareMethod::ACTION => Method\ShareMethod::class,
        Method\ShareCreateMethod::ACTION => Method\ShareCreateMethod::class,
        Method\ShareDeleteMethod::ACTION => Method\ShareDeleteMethod::class,
        Method\ShareEditMethod::ACTION => Method\ShareEditMethod::class,
        Method\BookmarksMethod::ACTION => Method\BookmarksMethod::class,
        Method\BookmarkCreateMethod::ACTION => Method\BookmarkCreateMethod::class,
        Method\BookmarkEditMethod::ACTION => Method\BookmarkEditMethod::class,
        Method\BookmarkDeleteMethod::ACTION => Method\BookmarkDeleteMethod::class,
        Method\VideosMethod::ACTION => Method\VideosMethod::class,
        Method\VideoMethod::ACTION => Method\VideoMethod::class,
        Method\StatsMethod::ACTION => Method\StatsMethod::class,
        Method\PodcastsMethod::ACTION => Method\PodcastsMethod::class,
        Method\PodcastMethod::ACTION => Method\PodcastMethod::class,
        Method\PodcastCreateMethod::ACTION => Method\PodcastCreateMethod::class,
        Method\PodcastDeleteMethod::ACTION => Method\PodcastDeleteMethod::class,
        Method\PodcastEditMethod::ACTION => Method\PodcastEditMethod::class,
        Method\PodcastEpisodesMethod::ACTION => Method\PodcastEpisodesMethod::class,
        Method\PodcastEpisodeMethod::ACTION => Method\PodcastEpisodeMethod::class,
        Method\PodcastEpisodeDeleteMethod::ACTION => Method\PodcastEpisodeDeleteMethod::class,
        Method\UsersMethod::ACTION => Method\UsersMethod::class,
        Method\UserMethod::ACTION => Method\UserMethod::class,
        Method\UserPreferencesMethod::ACTION => Method\UserPreferencesMethod::class,
        Method\UserPreferenceMethod::ACTION => Method\UserPreferenceMethod::class,
        Method\UserCreateMethod::ACTION => Method\UserCreateMethod::class,
        Method\UserUpdateMethod::ACTION => Method\UserUpdateMethod::class,
        Method\UserDeleteMethod::ACTION => Method\UserDeleteMethod::class,
        Method\FollowersMethod::ACTION => Method\FollowersMethod::class,
        Method\FollowingMethod::ACTION => Method\FollowingMethod::class,
        Method\ToggleFollowMethod::ACTION => Method\ToggleFollowMethod::class,
        Method\LastShoutsMethod::ACTION => Method\LastShoutsMethod::class,
        Method\RateMethod::ACTION => Method\RateMethod::class,
        Method\FlagMethod::ACTION => Method\FlagMethod::class,
        Method\RecordPlayMethod::ACTION => Method\RecordPlayMethod::class,
        Method\ScrobbleMethod::ACTION => Method\ScrobbleMethod::class,
        Method\CatalogsMethod::ACTION => Method\CatalogsMethod::class,
        Method\CatalogMethod::ACTION => Method\CatalogMethod::class,
        Method\CatalogActionMethod::ACTION => Method\CatalogActionMethod::class,
        Method\CatalogFileMethod::ACTION => Method\CatalogFileMethod::class,
        Method\TimelineMethod::ACTION => Method\TimelineMethod::class,
        Method\FriendsTimelineMethod::ACTION => Method\FriendsTimelineMethod::class,
        Method\UpdateFromTagsMethod::ACTION => Method\UpdateFromTagsMethod::class,
        Method\UpdateArtistInfoMethod::ACTION => Method\UpdateArtistInfoMethod::class,
        Method\UpdateArtMethod::ACTION => Method\UpdateArtMethod::class,
        Method\UpdatePodcastMethod::ACTION => Method\UpdatePodcastMethod::class,
        Method\StreamMethod::ACTION => Method\StreamMethod::class,
        Method\DownloadMethod::ACTION => Method\DownloadMethod::class,
        Method\GetArtMethod::ACTION => Method\GetArtMethod::class,
        Method\LocalplayMethod::ACTION => Method\LocalplayMethod::class,
        Method\LocalplaySongsMethod::ACTION => Method\LocalplaySongsMethod::class,
        Method\DemocraticMethod::ACTION => Method\DemocraticMethod::class,
        Method\SystemUpdateMethod::ACTION => Method\SystemUpdateMethod::class,
        Method\SystemPreferencesMethod::ACTION => Method\SystemPreferencesMethod::class,
        Method\SystemPreferenceMethod::ACTION => Method\SystemPreferenceMethod::class,
        Method\PreferenceCreateMethod::ACTION => Method\PreferenceCreateMethod::class,
        Method\PreferenceEditMethod::ACTION => Method\PreferenceEditMethod::class,
        Method\PreferenceDeleteMethod::ACTION => Method\PreferenceDeleteMethod::class,
    ];

    /**
     *  @var string $auth_version
     */
    public static $auth_version = '350001';

    /**
     * @var string $version
     */
    public static $version = '5.0.0';

    /**
     * @var Browse $browse
     */
    public static $browse = null;

    public static function getBrowse(): Browse
    {
        if (self::$browse === null) {
            self::$browse = new Browse(null, false);
        }

        return self::$browse;
    }

    /**
     * set_filter
     * MINIMUM_API_VERSION=380001
     *
     * This is a play on the browse function, it's different as we expose
     * the filters in a slightly different and vastly simpler way to the
     * end users--so we have to do a little extra work to make them work
     * internally.
     * @param string $filter
     * @param integer|string|boolean|null $value
     * @return boolean
     */
    public static function set_filter($filter, $value, ?Browse $browse = null)
    {
        if (!strlen((string)$value)) {
            return false;
        }

        if ($browse === null) {
            $browse = self::getBrowse();
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    $browse->set_filter('add_lt', strtotime((string) $elements['1']));
                    $browse->set_filter('add_gt', strtotime((string) $elements['0']));
                } else {
                    $browse->set_filter('add_gt', strtotime((string) $value));
                }
                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    $browse->set_filter('update_lt', strtotime((string) $elements['1']));
                    $browse->set_filter('update_gt', strtotime((string) $elements['0']));
                } else {
                    $browse->set_filter('update_gt', strtotime((string) $value));
                }
                break;
            case 'alpha_match':
                $browse->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                $browse->set_filter('exact_match', $value);
                break;
            case 'enabled':
                $browse->set_filter('enabled', $value);
                break;
            default:
                break;
        } // end filter

        return true;
    } // set_filter
}