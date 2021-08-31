<?php
namespace WP_CLI\Unlikely;

use ArrayObject;
use WP_CLI;
use WP_CLI_Command;

class HtmlToWxrCommand extends WP_CLI_Command
{
    public const STATUS_ERR     = 'ERROR';
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
    public $arg_container;
    /**
     * Sets up arguments container
     */
    public function __construct()
    {
        $this->arg_container = new class () extends ArrayObject
        {
            public $status = 'OK';
            public $error_msg = [];
            public function addErrorMessage($msg)
            {
                $this->status = HtmlToWxrCommand::STATUS_ERR;
                $this->error_msg[] = $msg;
            }
            public function getErrorMessages()
            {
                return implode("\n", $this->error_msg);
            }
        };
    }
    /**
     * @param array $args       Indexed array of positional arguments.
     * @param array $assoc_args Associative array of associative arguments.
     */
    public function __invoke( $args, $assoc_args )
    {
        $obj = $this->sanitizeParams($args, $assoc_args);
        if ($obj->status === self::STATUS_ERR) {
            WP_CLI::error($obj->getErrorMessages());
            exit;
        }
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
    protected function sanitizeParams(array $args, array $assoc)
    {
        // santize $config param
        $config = WP_CLI::line($args[0]) ?? '';
        $dest_dir = WP_CLI::line($args[1]) ?? '';
        if (empty($config) || empty($dest_dir) || !file_exists($config) || !file_exists($dest_dir)) {
            $this->arg_container->addErrorMessage(self::ERROR_POS_ARGS);
            return $this->arg_container;
        } else {
            $this->arg_container->offsetSet('config', $config);
            $this->arg_container->offsetSet('dest', $dest_dir);
        }
        // grab optional params
        $src = WP_CLI::line($assoc_args['src']) ?? \WP_CLI\Utils\get_home_dir();
        $single =  WP_CLI::line($assoc_args['single']) ?? '';
        $ext = WP_CLI::line($assoc_args['ext']) ?? 'html';
        // sanitize $src param
        if (!file_exists($src)) {
            error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SRC . ':' . $src);
            $this->arg_container->addErrorMessage(self::ERROR_SRC);
            return $this->arg_container;
        } else {
            $this->arg_container->offsetSet('src', $src);
        }
        // sanitize $single param
        if (!empty($single)) {
            if ($single[0] !== DIRECTORY_SEPARATOR) {
                $fn = $src . DIRECTORY_SEPARATOR . $single;
                $double = DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR;
                $fn = str_replace($double, DIRECTORY_SEPARATOR, $fn);
                if (!file_exists($fn)) {
                    error_log(__METHOD__ . ':' . __LINE__ . ':' . self::ERROR_SINGLE . ':' . $fn);
                    $this->arg_container->addErrorMessage(self::ERROR_SINGLE);
                    return $this->arg_container;
                } else {
                    $this->arg_container->offsetSet('single', $single);
                }
            }
        }
        // sanitize $ext
        if (strpos($ext, ',') !== FALSE) {
        $ext = explode(',', $ext);
        } else {
            $ext = [$ext];
        }
        $this->arg_container->offsetSet('ext', $ext);
        return $this->arg_container;
    }
}
