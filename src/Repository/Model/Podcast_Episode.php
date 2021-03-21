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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Podcast\PodcastEpisodeDeleterInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Module\Authorization\Access;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use PDOStatement;

class Podcast_Episode extends database_object implements Media, library_item
{
    protected const DB_TABLENAME = 'podcast_episode';

    public $id;
    public $title;
    public $guid;
    public $podcast;
    public $state;
    public $file;
    public $source;
    public $size;
    public $time;
    public $played;
    public $type;
    public $mime;
    public $website;
    public $description;
    public $author;
    public $category;
    public $pubdate;
    public $enabled;
    public $object_cnt;
    public $catalog;
    public $f_title;
    public $f_file;
    public $f_size;
    public $f_time;
    public $f_time_h;
    public $f_description;
    public $f_author;
    public $f_artist_full;
    public $f_category;
    public $f_website;
    public $f_pubdate;
    public $f_state;
    public $link;
    public $f_link;
    public $f_podcast;
    public $f_podcast_link;

    /**
     * Constructor
     *
     * Podcast Episode class
     * @param integer $podcastep_id
     */
    public function __construct($podcastep_id = null)
    {
        if ($podcastep_id === null) {
            return false;
        }

        $this->id = (int)$podcastep_id;

        if ($info = $this->get_info($this->id)) {
            foreach ($info as $key => $value) {
                $this->$key = $value;
            }
            if (!empty($this->file)) {
                $data          = pathinfo($this->file);
                $this->type    = strtolower((string)$data['extension']);
                $this->mime    = Song::type_to_mime($this->type);
                $this->enabled = true;
            }
        } else {
            $this->id = null;

            return false;
        }

        return true;
    } // constructor

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function isNew(): bool
    {
        return $this->getId() === 0;
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs()
    {
        return array($this->catalog);
    }

    /**
     * format
     * this function takes the object and reformats some values
     * @param boolean $details
     * @return boolean
     */
    public function format($details = true)
    {
        $this->f_title       = scrub_out($this->title);
        $this->f_description = scrub_out($this->description);
        $this->f_category    = scrub_out($this->category);
        $this->f_author      = scrub_out($this->author);
        $this->f_artist_full = $this->f_author;
        $this->f_website     = scrub_out($this->website);
        $this->f_pubdate     = get_datetime((int)$this->pubdate);
        $this->f_state       = ucfirst($this->state);

        // Format the Time
        $min            = floor($this->time / 60);
        $sec            = sprintf("%02d", ($this->time % 60));
        $this->f_time   = $min . ":" . $sec;
        $hour           = sprintf("%02d", floor($min / 60));
        $min_h          = sprintf("%02d", ($min % 60));
        $this->f_time_h = $hour . ":" . $min_h . ":" . $sec;
        // Format the Size
        $this->f_size = Ui::format_bytes($this->size);
        $this->f_file = $this->f_title . '.' . $this->type;

        $this->link   = AmpConfig::get('web_path') . '/podcast_episode.php?action=show&podcast_episode=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '" title="' . $this->f_title . '">' . $this->f_title . '</a>';

        if ($details) {
            $podcast = new Podcast($this->podcast);
            $podcast->format();
            $this->catalog        = $podcast->catalog;
            $this->f_podcast      = $podcast->getTitleFormatted();
            $this->f_podcast_link = $podcast->getLinkFormatted();
            $this->f_file         = $this->f_podcast . ' - ' . $this->f_file;
        }
        if (AmpConfig::get('show_played_times')) {
            $this->object_cnt = Stats::get_object_count('podcast_episode', $this->id);
        }

        return true;
    }

    /**
     * @return array|mixed
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->f_podcast
        );
        $keywords['title'] = array(
            'important' => true,
            'label' => T_('Title'),
            'value' => $this->f_title
        );

        return $keywords;
    }

    /**
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_title;
    }

    /**
     * @return array
     */
    public function get_parent()
    {
        return array('object_type' => 'podcast', 'object_id' => $this->podcast);
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return array();
    }

    /**
     * @param string $name
     * @return array
     */
    public function search_childrens($name)
    {
        debug_event(self::class, 'search_childrens ' . $name, 5);

        return array();
    }

    /**
     * @param string $filter_type
     * @return array|mixed
     */
    public function get_medias($filter_type = null)
    {
        $medias = array();
        if ($filter_type === null || $filter_type == 'podcast_episode') {
            $medias[] = array(
                'object_type' => 'podcast_episode',
                'object_id' => $this->id
            );
        }

        return $medias;
    }

    /**
     * @return mixed|null
     */
    public function get_user_owner()
    {
        return null;
    }

    /**
     * @return string
     */
    public function get_default_art_kind()
    {
        return 'default';
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->f_description;
    }

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb = 2, $force = false)
    {
        $episode_id = null;
        $type       = null;

        if (Art::has_db($this->id, 'podcast_episode')) {
            $episode_id = $this->id;
            $type       = 'podcast_episode';
        } else {
            if (Art::has_db($this->podcast, 'podcast') || $force) {
                $episode_id = $this->podcast;
                $type       = 'podcast';
            }
        }

        if ($episode_id !== null && $type !== null) {
            Art::display($type, $episode_id, $this->get_fullname(), $thumb, $this->link);
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast episode
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        $title       = isset($data['title']) ? $data['title'] : $this->title;
        $website     = isset($data['website']) ? $data['website'] : $this->website;
        $description = isset($data['description']) ? $data['description'] : $this->description;
        $author      = isset($data['author']) ? $data['author'] : $this->author;
        $category    = isset($data['category']) ? $data['category'] : $this->category;

        $sql = 'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?';
        Dba::write($sql, array($title, $website, $description, $author, $category, $this->id));

        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->author      = $author;
        $this->category    = $category;

        return $this->id;
    }

    /**
     * set_played
     * this checks to see if the current object has been played
     * if not then it sets it to played. In any case it updates stats.
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null)
    {
        // ignore duplicates or skip the last track
        if ($this->check_play_history($user, $agent, $date)) {
            Stats::insert('podcast_episode', $this->id, $user, $agent, $location, 'stream', $date);
        }

        if (!$this->played) {
            if (!Access::check('interface', 25)) {
                return false;
            }

            $sql = "UPDATE `podcast_episode` SET `played` = ? WHERE `id` = ?";
            Dba::write($sql, [1, $this->id]);

            return true;
        }

        return true;
    } // set_played

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date)
    {
        return Stats::has_played_history($this, $user, $agent, $date);
    }

    /**
     * Get stream name.
     * @return string
     */
    public function get_stream_name()
    {
        return $this->f_podcast . " - " . $this->f_title;
    }

    /**
     * Get transcode settings.
     * @param string $target
     * @param string $player
     * @param array $options
     * @return array
     */
    public function get_transcode_settings($target = null, $player = null, $options = array())
    {
        return Song::get_transcode_settings_for_media($this->type, $target, $player, 'song', $options);
    }

    /**
     * play_url
     * This function takes all the song information and correctly formats a
     * a stream URL taking into account the downsmapling mojo and everything
     * else, this is the true function
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     * @param string $uid
     * @return string
     */
    public function play_url($additional_params = '', $player = '', $local = false, $uid = false)
    {
        if (!$this->id) {
            return '';
        }
        if (!$uid) {
            // No user in the case of upnp. Set to 0 instead. required to fix database insertion errors
            $uid = Core::get_global('user')->id ?: 0;
        }
        // set no use when using auth
        if (!AmpConfig::get('use_auth') && !AmpConfig::get('require_session')) {
            $uid = -1;
        }

        $type = $this->type;

        $this->format();
        $media_name = $this->get_stream_name() . "." . $type;
        $media_name = preg_replace("/[^a-zA-Z0-9\. ]+/", "-", $media_name);
        $media_name = rawurlencode($media_name);

        $url = Stream::get_base_url($local) . "type=podcast_episode&oid=" . $this->id . "&uid=" . (string) $uid . '&format=raw' . $additional_params;
        if ($player !== '') {
            $url .= "&player=" . $player;
        }
        $url .= "&name=" . $media_name;

        return Stream_Url::format($url);
    }

    /**
     * Get stream types.
     * @param string $player
     * @return array
     */
    public function get_stream_types($player = null)
    {
        return Song::get_stream_types_for_type($this->type, $player);
    }

    /**
     * remove
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        return $this->getPodcastEpisodeDeleter()->delete($this);
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeDeleter(): PodcastEpisodeDeleterInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeDeleterInterface::class);
    }
}
