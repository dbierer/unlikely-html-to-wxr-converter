<?php
namespace WP_CLI\Unlikely\Import;

/*
 * Unlikely\Import\Extract
 *
 * Extracts clean HTML fragment from HTML file
 *
 * @author doug@unlikelysource.com
 * @date 2021-08-21
 * Copyright 2021 unlikelysource.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 */

use Exception;
use DateTime;
use DateTimeZone;
use SplFileObject;

class Extract implements BuildWXRInterface
{
    public const DELIM_START  = '<body>';
    public const DELIM_STOP   = '</body>';
    public const TITLE_REGEX  = '!\<title\>(.+?)\<\/title\>!';
    public const EXCERPT_TAGS = ['h2' => 'p', 'p' => 'p'];
    public const ERR_DELIM    = 'ERROR: beginning or end delimiter not found';
    public const ERR_FILE     = 'ERROR: HTML file not found';
    public const ERR_READ     = 'ERROR: unable to read HTML file';
    public const ERR_CALLBACK = 'ERROR: transform callback is not callable';
    public const DEFAULT_TZ   = 'PST';
    public const DEFAULT_TITLE = 'Title Unknown';
    public const DEFAULT_EXCERPT = '';
    public const DEFAULT_ATTR_LIST = ['width','height','style','class'];
    public $err         = [];
    public $config      = [];
    public $contents    = '';
    public $file_obj    = NULL;
    public $next_id     = 1;
    /**
     * Initializes delimiters and creates transform callback array
     *
     * @param string $fn    : filename of HTML document
     * @param array $config : ['delim_start' => XXX, 'delim_stop' => YYY]
     * @throws Exception : if $fn has no contents or doesn't exist
     */
    public function __construct(string $fn, array $config)
    {
        $this->config    = $config[__CLASS__] ?? [];
        $this->resetFile($fn);
    }
    /**
     * Needed to maintain consistency with BuildWXRInterface
     */
    public function setBuildWXRInstance(BuildWXR $build)
    {
        /* do nothing */
    }
    /**
     * Resets filename
     *
     * @param string $fn : filename of HTML document
     * @param int $next_id
     * @return void
     * @throws Exception if file doesn't exist or is empty
     */
    public function resetFile(string $fn, int $next_id = 1)
    {
        $this->err = [];
        $this->contents = $this->getContents($fn, $this->err);
        // bail out if unable to open $fn or no contents
        if (empty($this->contents)) {
            throw new Exception(static::ERR_FILE);
        }
        $this->file_obj  = new SplFileObject($fn, 'r');
        $this->next_id   = $next_id;
    }
    /**
     * Returns the next post ID number
     * Increments $this->next_id
     *
     * @return int $id
     */
    public function getNextId() : int
    {
        return $this->next_id++;
    }
    /**
     * Grabs contents of the file
     * Removes "\r"
     * Replaces "\n" with " "
     *
     * @param string $fn : filename of HTML document
     * @param array $err : error messages (passed by reference)
     * @return string|FALSE
     */
    public function getContents(string $fn, array &$err)
    {
        $results = '';
        if (!file_exists($fn)) {
            $err[] = static::ERR_FILE;
            $results = FALSE;
        } else {
            $results = file_get_contents($fn);
            if ($results) {
                $results = str_replace(["\r","\n"],['', ' '], $results);
            } else {
                $err[] = static::ERR_READ;
            }
        }
        return $results;
    }
    /**
     * Returns creation date of file
     *
     * @param ?string $tz : timezone : if blank, don't use
     * @return string $date in RSS format
     */
    public function getCreateDate(?string $tz = '') : string
    {
        $tz  = (empty($tz)) ? static::DEFAULT_TZ : $tz;
        $obj = new DateTime('@' . $this->file_obj->getCTime());
        $obj->setTimeZone(new DateTimeZone($tz));
        return $obj->format(DATE_RSS);
    }
    /**
     * Returns modification date of file
     *
     * @param ?string $tz : timezone : if blank, don't use
     * @return string $date in RSS format
     */
    public function getModifyDate(?string $tz = '') : string
    {
        $tz  = (empty($tz)) ? static::DEFAULT_TZ : $tz;
        $obj = new DateTime('@' . $this->file_obj->getMTime());
        $obj->setTimeZone(new DateTimeZone($tz));
        return $obj->format(DATE_RSS);
    }
    /**
     * Returns immediate directory name of file
     *
     * @return string $dir
     */
    public function getLastDir() : string
    {
        return basename($this->file_obj->getPath());
    }
    /**
     * Returns filename minus extension in WP format
     *
     * @return string $wp_fn
     */
    public function getWpfilename() : string
    {
        $fn = $this->file_obj->getBasename();
        $pos = strpos($fn, '.');
        $wpFn = substr($fn, 0, $pos);
        $wpFn = str_replace(['_'], ['-'], $wpFn);
        return $wpFn;
    }
    /**
     * Returns link in WP format
     *
     * @param string $url
     * @return string $wp_fn
     */
    public function getWpLink(string $url) : string
    {
        $link = $this->getLastDir() . '/' . $this->getWpFilename();
        $link = str_replace('//', '/', $link);
        if ($url[-1] === '/') {
            $link = $url . $link;
        } else {
            $link = $url . '/' . $link;
        }
        return $link;
    }
    /**
     * Extracts title between title delimiters
     *
     * @return string $title
     */
    public function getTitle()
    {
        $title_regex  = $this->config['title_regex'] ?? static::TITLE_REGEX;
        $matches = [];
        preg_match($title_regex, $this->contents, $matches);
        return (empty($matches[1]))
                ? static::DEFAULT_TITLE
                : $matches[1];
    }
    /**
     * Extracts excerpt between excerpt delimiters
     *
     * @return string $excerpt
     */
    public function getExcerpt()
    {
        $matches = [];
        $excerpt_tags = $this->config['excerpt_tags'] ?? static::EXCERPT_TAGS;
        if (!is_array($excerpt_tags)) return '';
        $str = $this->contents;
        $offset = 0;
        foreach ($excerpt_tags as $start => $stop) {
            $stop = (empty($stop)) ? $start : $stop;
            $str = $this->doExtract($str, $start, $stop);
        }
        return trim(strip_tags($str));
    }
    /**
     * Performs actual extraction between search boundaries
     *
     * @param string $str   : contents to search
     * @param string $start : tag that defines start of search block
     * @param string $stop  : tag that defines end of search block
     * @return string $result : extracted contents or ''
     */
    public function doExtract(string &$str, string $start, string $stop) : string
    {
        $begin = 0;
        $end   = 0;
        $open = '<' . $start;
        $close = '</' . $stop . '>';
        $begin = stripos($str, $open);
        if ($begin !== FALSE) {
            $end = stripos($str, $close, $begin) + strlen($close);
            $offset = $begin;
        }
        $str = ($begin < $end)
                ? substr($str, $begin, $end - $begin)
                : '';
        return $str;
    }
    /**
     * Extracts content between delimiters and returns clean HTML
     * NOTE: requires the "tidy" extension to be enabled
     *
     * @param ?array $err : error messages (passed by reference)
     * @return string $html : clean HTML; returns '' if unable to process content
     * @throws Exception :; if "transform" => "callback" value is not callable
     */
    public function getHtml(?array &$err = [])
    {
        // init vars
        $html = '';
        $delim_start  = $this->config['delim_start'] ?? static::DELIM_START;
        $delim_stop   = $this->config['delim_stop']  ?? static::DELIM_STOP;
        if (strpos($this->contents, $delim_start) === FALSE
            || strpos($this->contents, $delim_stop) === FALSE) {
            $err[] = self::ERR_DELIM;
            return $html;
        }
        // extract contents
        $after = explode($delim_start, $this->contents)[1] ?? '';
        if (!empty($after)) {
            $middle = explode($delim_stop, $after)[0] ?? '';
            if (!empty($middle)) {
                $html  = $middle;
                // perform tranformations
                $transform = $this->config['transform'] ?? [];
                foreach ($transform as $item) {
                    if (empty($item['callback'])) continue;
                    $callback = $item['callback'];
                    if (!is_callable($callback))
                        throw new Exception(static::ERR_CALLBACK);
                    $params = $item['params'] ?? [];
                    $html   = $callback($html, $params);
                }
            }
        }
        return $html;
    }
}
