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
    public $tr = '';  // row class (default == "row")
    public $td = '';  // column class (default == "col")
    public $th = '';  // header column class (default == "col bold")
    public const DEFAULT_TR = 'row';
    public const DEFAULT_TD = 'col';
    public const DEFAULT_TH = 'col bold';
    /**
     * Converts HTML <table><tr><td>|<th> to <div class="row"><div class="col">
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['td'   => : td class (default: "col")
     *                         'th'   => : td class (default: "col bold")
     *                         'tr'   => : row class (default: "row")]
     * @return string $html : HTML with <table><tr><td>|<th> conversions done
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $this->init($params);
        return $this->convert($html);
    }
    /**
     * Initializes properties
     *
     * @param array $params : ['tr'   => : row class
     *                         'td'   => : column class
     *                         'th' =>   : header column class
     * @return void
     * @thtrs InvalidArgumentException
     */
    public function init(array $params) : void
    {
        $this->td = $params['td'] ?? static::DEFAULT_TD;
        $this->tr = $params['tr'] ?? static::DEFAULT_TR;
        $this->th = $params['th'] ?? static::DEFAULT_TH;
    }
    /**
     * Performs conversion:
     * -- removes table tags
     * -- converts <tr> into <div class="$this->tr">
     * -- converts <td> into <div class="$this->td">
     * -- converts <th> into <div class="$this->th">
     *
     * @param string $html : HTML string to be cleaned
     * @return string $html : HTML with <tr></tr> conversions done
     */
    public function convert(string $html) : string
    {
        $html = $this->removeTableTags($html);
        $html = $this->convertRow($html);
        $html = $this->convertCol($html);
        return $html;
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
        $html = preg_replace($search, '<div class="' . $this->tr . '">', $html);
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
        $search = '!<td.*?>!i';
        $html = preg_replace($search, '<div class="' . $this->td . '">', $html);
        $html = str_ireplace('</td>', '</div>', $html);
        $search = '!<th.*?>!i';
        $html = preg_replace($search, '<div class="' . $this->th . '">', $html);
        $html = str_ireplace('</th>', '</div>', $html);
        return $html;
    }
}
