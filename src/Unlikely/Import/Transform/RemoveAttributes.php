<?php
namespace WP_CLI\Unlikely\Import\Transform;

/*
 * Unlikely\Import\Transform\RemoveAttributes
 *
 * Removes listed attributes
 *
 * @author doug@unlikelysource.com
 * @date 2021-08-18
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
class RemoveAttributes implements TransformInterface
{
    /**
     * Removes listed attributes
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['attributes' => [array,of,attributes,to,remove]]
     * @return string $html : HTML with listed attributes removed
     */
    public function __invoke(string $html, array $params = []) : string
    {
        $blank = '!\b%s=".*?"!';
        $list  = $params['attributes'] ?? [];
        foreach ($list as $attrib) {
            $patt = sprintf($blank, $attrib);
            $html = preg_replace($patt, ' ', $html);
            $html = str_replace('  ',' ',$html);
        }
        $html = str_replace('  ',' ',$html);
        $html = str_replace(' >','>',$html);
        return $html;
    }
}
