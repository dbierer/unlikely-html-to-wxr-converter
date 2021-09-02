<?php
namespace WP_CLI\Unlikely\Import\Transform;

/*
 * Unlikely\Import\Transform\Clean
 *
 * Removes blocks based up search criteria, start and stop strings
 *
 * @author doug@unlikelysource.com
 * @date 2021-09-02
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

use InvalidArgumentException;
class RemoveBlock implements TransformInterface
{
    public $start = NULL;  // starting string
    public $stop  = NULL;  // ending string
    public $items = [];    // array of search items used to confirm block to be removed
    public $beg_pos = NULL;  // start pos of block to be removed
    public $end_pos = NULL;  // end pos of block to be removed
    public const ERR_PARAMS = 'ERROR: parameter array must contain the keys "start", "stop" and "items"';
    /**
     * Removes blocks based up search criteria, start and stop strings
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['start' => : starting string for block to be removed,
     *                         'stop'  => : ending string for block to be removed; must occur *after* start string
     *                         'items' => : array of strings that occur between "start" and "stop", used to correctly identify block to be removed
     * @return string $html : HTML with identified block removed
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $this->init($params);
        if ($this->getStartAndStop($html)) {
            if ($this->confirm($html, $this->items)) {
                $html = $this->remove($html);
            }
        }
        return $html;
    }
    /**
     * Initializes properties
     *
     * @param array $params : ['start' => : starting string for block to be removed,
     *                         'stop'  => : ending string for block to be removed; must occur *after* start string
     *                         'items' => : array of strings that occur between "start" and "stop", used to correctly identify block to be removed
     * @return void
     * @throws InvalidArgumentException
     */
    public function init(array $params) : void
    {
        $this->start = $params['start'] ?? '';
        $this->stop  = $params['stop']  ?? '';
        $this->items = $params['items'] ?? [];
        if (empty($this->start) || empty($this->stop) || empty($this->items))
            throw new InvalidArgumentException(self::ERR_PARAMS);
    }
    /**
     * Populates $this->beg_pos and $this->end_pos
     *
     * @param string $contents : document to be searched
     * @return bool TRUE if both contain beg_pos and end_pos values, and beg_pos < end_pos; FALSE otherwise
     */
    public function getStartAndStop(string $contents)
    {
        $this->beg_pos = strpos($contents, $this->start);
        $this->end_pos = strpos($contents, $this->stop);
        $valid = 4;
        $found = 0;
        $found += (int) (is_int($this->beg_pos));
        $found += (int) (is_int($this->end_pos));
        $found += (int) (((int) $this->end_pos) < strlen($contents));
        $found += (int) ($this->beg_pos < $this->end_pos);
        return ($found === $valid);
    }
    /**
     * Confirms that all items in $search exist between $this->start and $this->stop
     *
     * @param string $contents : document to be searched
     * @return bool TRUE if all items found; FALSE otherwise
     */
    public function confirm(string $contents)
    {
        $max = count($this->items);
        $found = 0;
        foreach ($this->items as $needle) {
            $pos = strpos($contents, $needle);
            if ($pos !== FALSE
                && $pos > $this->beg_pos
                && $pos < $this->end_pos) { $found++; }
        }
        return ($found === $max);
    }
    /**
     * Removes block between $this->beg_pos and $this->end_pos
     *
     * @return string $contents : HTML with block removed
     */
    public function remove(string $contents)
    {
        $begin = $this->beg_pos;
        $end   = $this->end_pos + strlen($this->stop);
        $first = substr($contents, 0, $begin);
        $last  = substr($contents, $end);
        $contents = $first . $last;
        return $contents;
    }
}
