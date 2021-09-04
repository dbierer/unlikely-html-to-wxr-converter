<?php
namespace WP_CLI\Unlikely;
/**
 * Main wp-cli command class
 *
 * @author doug@unlikelysource.com
 * @date 2021-09-01
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

use ArrayObject;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilterIterator;
use WP_CLI;
use WP_CLI_Command;

class HtmlToWxrCommand extends WP_CLI_Command
{
    public const ERROR_POS_ARGS = 'ERROR: path to config file and/or destination directory to write WXR files missing or invalid';
    public const ERROR_SINGLE   = 'ERROR: single file not found';
    public const ERROR_SRC      = 'ERROR: source directory path not found';
    public const SYNOPSIS = [
        'shortdesc' => 'Converts one or more HTML files to WordPress WXR import format',
        'synopsis' => [
            [
                'type'        => 'positional',
                'name'        => 'config',
                'description' => 'Path to the configuration file',
                'optional'    => false,
                'repeating'   => false,
            ],
            [
                'type'        => 'positional',
                'name'        => 'dest',
                'description' => 'Directory where WXR files will be stored for later import',
                'optional'    => false,
                'repeating'   => false,
            ],
            [
                'type'        => 'positional',
                'name'        => 'next_id',
                'description' => 'Next post ID number',
                'optional'    => false,
                'repeating'   => false,
            ],
            [
                'type'        => 'assoc',
                'name'        => 'src',
                'description' => 'Starting directory path indicates where to start converting.',
                'optional'    => true,
                'default'     => 'current directory',
                //'options'     => [ 'success', 'error' ],
            ],
            [
                'type'        => 'assoc',
                'name'        => 'single',
                'description' => 'Single file to convert. If full path to file is not provided, prepends the value of "src" to "single"',
                'optional'    => true,
                'default'     => 'none',
                //'options'     => [ 'success', 'error' ],
            ],
            [
                'type'        => 'assoc',
                'name'        => 'ext',
                'description' => 'Extension(s) other than "html" to convert.  If multiple extension, separate extensions with comma(s)',
                'optional'    => true,
                'default'     => 'html',
                //'options'     => [ 'success', 'error' ],
            ],
        ],
        'when' => 'after_wp_load',
        'longdesc' =>   '## EXAMPLES' . "\n\n" . 'wp html-to-wxr /config/config.php /tmp  --src=/httpdocs --ext=htm,html,phtml',
    ];
    public $container;
    /**
     * @param array $args       Indexed array of positional arguments.
     * @param array $assoc_args Associative array of associative arguments.
     */
    public function __invoke($args, $assoc_args)
    {
        $container = $this->sanitizeParams($args, $assoc_args);
        if ($container->status === ArgsContainer::STATUS_ERR) {
            WP_CLI::error_multi_line($obj->getErrorMessages(), TRUE);
        }
        // if single, convert single file
        if (!empty($container['single'])) {
            $extract = new Extract($container['single'], $container['config']);
            $this->convertSingle($extract, $container);
        } else {
            // otherwise build a list of files
            $iter = $this->getDirIterator($container);
            $next_id = $container['next_id'];
            // loop through list
            $iter->rewind();
            while ($iter->valid()) {
                $name = $iter->key();
                if (empty($extract)) {
                    $extract = new Extract($name, $container['config']);
                } else {
                    $extract->resetFile($name, $next_id++);
                }
                $this->convertSingle($extract, $container);
                $iter->next();
            }
        }
    }
    /**
     * Converts a single file
     *
     * @param Extract $extract
     * @param ArrayObject $container
     * @return string $xml_fn : full path to XML file | NULL if none written
     */
    public function convertSingle(Extract $extract, ArrayObject $container)
    {
        $result  = NULL;
        $build   = new BuildWXR($container['config'], $extract);
        // build target path and FN
        $fn = $extract->file_obj->getBasename($extract->file_obj->getExtension());
        $dest = $container['dest'] . DIRECTORY_SEPARATOR . $fn . '.xml';
        $double = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
        $dest = str_replace($double, DIRECTORY_SEPARATOR, $dest);
        if ($build->buildWxr($dest)) $result = $dest;
        return $result;
    }
    /**
     * Returns recursive directory iteration
     *
     * @param ArrayObject $container
     * @return iterable $dirIterator : filtered recursive directory iterator
     */
    public function getDirIterator(ArrayObject $container) : iterable
    {
        $src = $container['src'];   // path to start recursion
        $ext = $container['ext'];   // array of extensions to include
        $iter = new RecursiveDirectoryIterator($src);
        $iterPlus = new RecursiveIteratorIterator($iter);
        $filtIter = new class ($iterPlus, $ext) extends FilterIterator {
            public $ext = [];
            public function __construct($iter, $ext)
            {
                parent::__construct($iter);
                $this->ext = $ext;
            }
            public function accept()
            {
                $info = pathinfo($this->key());
                return (in_array($info['extension'], $this->ext));
            }
        };
        return $filtIter;
    }
    /**
     * Sanitizes incoming args
     * Error status is stored in $arg_container->error
     * Error messages are stored in $arg_container->error_msg
     *
     * @param array $args : positional params
     * @param array $assoc : names params
     * @return ArrayObject $arg_container | FALSE
     */
    public function sanitizeParams(array $args, array $assoc)
    {
        // santize $config param
        $container = new ArgsContainer();
        $config = WP_CLI::line($args[0]) ?? '';
        $dest_dir = WP_CLI::line($args[1]) ?? '';
        $next_id  = WP_CLI::line($args[2]) ?? 0;
        if (empty($config) || empty($dest_dir) || !file_exists($config) || !file_exists($dest_dir)) {
            $container->addErrorMessage(self::ERROR_POS_ARGS);
            return $container;
        } else {
            $container->offsetSet('config', require $config);
            $container->offsetSet('dest', $dest_dir);
            $container->offsetSet('next_id', $next_id);
        }
        // grab optional params
        $src    = WP_CLI::line($assoc_args['src']) ?? \WP_CLI\Utils\get_home_dir();
        $single = WP_CLI::line($assoc_args['single']) ?? '';
        $ext    = WP_CLI::line($assoc_args['ext']) ?? 'html';
        // sanitize $src param
        if (!file_exists($src)) {
            error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SRC . ':' . $src);
            $container->addErrorMessage(self::ERROR_SRC);
            return $container;
        } else {
            $container->offsetSet('src', $src);
        }
        // sanitize $single param
        if (!empty($single)) {
            if ($single[0] !== DIRECTORY_SEPARATOR) {
                $fn = $src . DIRECTORY_SEPARATOR . $single;
                $double = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
                $fn = str_replace($double, DIRECTORY_SEPARATOR, $fn);
                if (!file_exists($fn)) {
                    error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SINGLE . ':' . $fn);
                    $container->addErrorMessage(self::ERROR_SINGLE);
                    return $container;
                } else {
                    $container->offsetSet('single', $single);
                }
            }
        }
        // sanitize $ext
        if (strpos($ext, ',') !== FALSE) {
        $ext = explode(',', $ext);
        } else {
            $ext = [$ext];
        }
        $container->offsetSet('ext', $ext);
        return $container;
    }
}
