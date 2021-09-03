<?php
namespace WP_CLI\Unlikely\Import\Transform;

/*
 * Unlikely\Import\Transform\Clean
 *
 * Uses Tidy extension to clean up HTML fragment
 * Removes extra header and footer added by Tidy
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
class Clean implements TransformInterface
{
    /**
     * Cleans up HTML using Tidy extension
     * If Tidy extension is not available, makes note in error log and return HTML untouched
     *
     * @param string $html : HTML string to be cleaned
     * @param array $params : ['bodyOnly' => : set TRUE (default) to only return content between <body>*</body>]
     * @return string $html : cleaned HTML
     */
    public function __invoke(string $html, array $params = []) : string
    {
        // if Tidy extension is available, perform cleanup
        if (function_exists('tidy_error_count')) {
            $tidy = new \tidy();
            $html = $tidy->repairString($html);
            $html = trim(str_replace("\n", '', $html));
            $bodyOnly = $params['bodyOnly'] ?? TRUE;
            if ($bodyOnly) {
                $matches = [];
                preg_match('!\<body\>(.+?)\<\/body\>!ims', $html, $matches);
                if (!empty($matches[1])) $html = $matches[1];
            }
        }
        return $html;
    }
}
