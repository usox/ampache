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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Repository\PodcastEpisodeRepositoryInterface;

class Podcast extends database_object implements library_item
{
    protected const DB_TABLENAME = 'podcast';

    /* Variables from DB */
    public $id;
    public $catalog;
    public $feed;
    public $title;
    public $website;
    public $description;
    public $language;
    public $copyright;
    public $generator;
    public $lastbuilddate;
    public $lastsync;

    public $episodes;
    public $f_website;
    public $f_description;
    public $f_language;
    public $f_copyright;
    public $f_generator;
    public $f_lastbuilddate;
    public $f_lastsync;

    /**
     * Podcast
     * Takes the ID of the podcast and pulls the info from the db
     * @param integer $podcast_id
     */
    public function __construct($podcast_id = 0)
    {
        /* If they failed to pass in an id, just run for it */
        if (!$podcast_id) {
            return false;
        }

        /* Get the information from the db */
        $info = $this->get_info($podcast_id);

        foreach ($info as $key => $value) {
            $this->$key = $value;
        } // foreach info

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
        $this->f_description   = scrub_out($this->description);
        $this->f_language      = scrub_out($this->language);
        $this->f_copyright     = scrub_out($this->copyright);
        $this->f_generator     = scrub_out($this->generator);
        $this->f_website       = scrub_out($this->website);
        $this->f_lastbuilddate = get_datetime((int)$this->lastbuilddate);
        $this->f_lastsync      = get_datetime((int)$this->lastsync);

        return true;
    }

    public function getEpisodeCount(): int
    {
        $cache = static::getDatabaseObjectCache();
        // Try to find it in the cache and save ourselves the trouble
        $cacheItem = $cache->retrieve('podcast_extra', $this->getId());
        if ($cacheItem !== []) {
            $row = $cacheItem;
        } else {
            $sql        = "SELECT COUNT(`podcast_episode`.`id`) AS `episode_count` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ?";
            $db_results = Dba::read($sql, [$this->getId()]);
            $row        = Dba::fetch_assoc($db_results);

            $cache->add('podcast_extra', $this->getId(), $row);
        }

        return (int) $row['episode_count'];
    }

    public function getLink(): string
    {
        return sprintf(
            '%s/podcast.php?action=show&podcast=%d',
            AmpConfig::get('web_path'),
            $this->getId()
        );
    }

    public function getLinkFormatted(): string
    {
        $title = $this->getTitleFormatted();

        return sprintf(
            '<a href="%s" title="%s">%s</a>',
            $this->getLink(),
            $title,
            $title
        );
    }

    public function getTitleFormatted(): string
    {
        return scrub_out($this->title);
    }

    /**
     * get_keywords
     * @return array
     */
    public function get_keywords()
    {
        $keywords            = array();
        $keywords['podcast'] = array(
            'important' => true,
            'label' => T_('Podcast'),
            'value' => $this->getTitleFormatted()
        );

        return $keywords;
    }

    /**
     * get_fullname
     *
     * @return string
     */
    public function get_fullname()
    {
        return $this->getTitleFormatted();
    }

    /**
     * @return null
     */
    public function get_parent()
    {
        return null;
    }

    /**
     * @return array
     */
    public function get_childrens()
    {
        return [
            'podcast_episode' => $this->getPodcastEpisodeRepository()->getEpisodeIds($this)
        ];
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
            $episodes = $this->getPodcastEpisodeRepository()->getEpisodeIds($this);
            foreach ($episodes as $episode_id) {
                $medias[] = array(
                    'object_type' => 'podcast_episode',
                    'object_id' => $episode_id
                );
            }
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
     * get_description
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
        if (Art::has_db($this->id, 'podcast') || $force) {
            Art::display('podcast', $this->id, $this->get_fullname(), $thumb, $this->getLink());
        }
    }

    /**
     * update
     * This takes a key'd array of data and updates the current podcast
     * @param array $data
     * @return mixed
     */
    public function update(array $data)
    {
        $feed        = isset($data['feed']) ? $data['feed'] : $this->feed;
        $title       = isset($data['title']) ? scrub_in($data['title']) : $this->title;
        $website     = isset($data['website']) ? scrub_in($data['website']) : $this->website;
        $description = isset($data['description']) ? scrub_in($data['description']) : $this->description;
        $generator   = isset($data['generator']) ? scrub_in($data['generator']) : $this->generator;
        $copyright   = isset($data['copyright']) ? scrub_in($data['copyright']) : $this->copyright;

        if (strpos($feed, "http://") !== 0 && strpos($feed, "https://") !== 0) {
            debug_event(self::class, 'Podcast update canceled, bad feed url.', 1);

            return $this->id;
        }

        $sql = 'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?';
        Dba::write($sql, array($feed, $title, $website, $description, $generator, $copyright, $this->id));

        $this->feed        = $feed;
        $this->title       = $title;
        $this->website     = $website;
        $this->description = $description;
        $this->generator   = $generator;
        $this->copyright   = $copyright;

        return $this->id;
    }

    /**
     * get_root_path
     * @return string
     */
    public function get_root_path()
    {
        $catalog = Catalog::create_from_id($this->catalog);
        if (!$catalog->get_type() == 'local') {
            debug_event(self::class, 'Bad catalog type.', 1);

            return '';
        }

        $dirname = $this->title;

        // create path if it doesn't exist
        if (!is_dir($catalog->path . DIRECTORY_SEPARATOR . $dirname)) {
            $path = $catalog->path . DIRECTORY_SEPARATOR . $dirname;
            if (!is_dir($path)) {
                if (mkdir($path) === false) {
                    debug_event(__CLASS__, 'Cannot create directory ' . $path, 2);
                }
            }
        }

        return $catalog->path . DIRECTORY_SEPARATOR . $dirname;
    }

    /**
     * @deprecated Inject by constructor
     */
    private function getPodcastEpisodeRepository(): PodcastEpisodeRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeRepositoryInterface::class);
    }
}
