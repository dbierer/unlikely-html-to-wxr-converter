<?php
namespace WP_CLI\Unlikely\Import\Transform;

/*
 * Unlikely\Import\Transform\Clean
 *
 * Converts HTML <table><tr><td>|<th> to <div class="row"><div class="col-xxx">
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
class TableToDiv implements TransformInterface
{
    public $col   = NULL;  // sprintf() string to format <div class="col-xxx">
    public $row   = 'row'; // row class (default == "row")
    public $width = 12;    // represents the max value for col class (default 12)
    public const ERR_PARAMS = 'ERROR: parameter array must contain the keys "row, "col" and "width"';
    /**
     * Converts HTML <table><tr><td>|<th> to <div class="row"><div class="col-xxx">
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['col'   => : sprintf() pattern for column <div> tags
     *                         'row'   => : row class
     *                         'width' => : represents the max value for col class (default 12)
     * @return string $html : HTML with <table><tr><td>|<th> conversions done
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $this->init($params);
        $html = $this->convertRow($html);
        $html = $this->convertCol($html);
        return $html;
    }
    /**
     * Initializes properties
     *
     * @param array $params : ['col'   => : sprintf() pattern for column <div> tags
     *                         'row'   => : row class
     *                         'width' => : represents the max value for col class (default 12)
     * @return void
     * @throws InvalidArgumentException
     */
    public function init(array $params) : void
    {
        $this->col   = $params['col']   ?? '';
        $this->row   = $params['row']   ?? '';
        $this->width = $params['width'] ?? [];
        if (empty($this->col) || empty($this->row) || empty($this->width))
            throw new InvalidArgumentException(self::ERR_PARAMS);
    }
    /**
     * Removes "<table>" and "</table>"
     *
     * @param string $html : HTML string to be cleaned
     * @return string $html : HTML with table tags removed
     */
    public function removeTableTags(string $html) : string
    {
        $search = '!<table.*?>!i';
        $html = preg_replace($search, '', $html);
        $html = str_ireplace('</table>', '', $html);
        return $html;
    }
    /**
     * Convert <tr> => <div class="row">
     *
     * @param string $html : HTML string to be cleaned
     * @return string $html : HTML with <tr></tr> conversions done
     */
    public function convertRow(string $html) : string
    {
        $search = '!<tr.*?>!i';
        $html = preg_replace($search, '<div class="' . $this->row . '">', $html);
        $html = str_ireplace('</tr>', '</div>', $html);
        return $html;
    }
    /**
     * Converts <td> => <div class="col-XXX"> and <th> => <div class="col-XXX"><b>
     * Converts </td> => </div> and </th> => </b></div>
     *
     * @param string $html : HTML string to be cleaned
     * @return string $html : HTML with <tr></tr> conversions done
     */
    public function convertCol(string $html) : string
    {
        // search for <td>|<th> with "width=" attribute
        $patt = '<td.*?width="(.+?)".*?>!i';
        $matches = [];
        preg_match_all($patt, $html, $matches);
        return $matches;
        /*
            // if "%" type width: determine width % with col pattern where 100% === col-XX-{$this->width}
            // if "px" or "pt" or just a number type width: determine width % with col pattern where NNN(px|pt)? === col-XX-{$this->width}
        // if no "width" then count the # <td>|<th> elements between <tr></tr>
            // define col-XX-NN size as even division of {$this->width}
        // replace ending tags
        $html = preg_replace($search, '<div class="' . $this->row . '">', $html);
        $html = str_ireplace(['</td>','</th>'], ['</div>','</b></div>'], $html);
        return $html;
        */
    }
}
