<?php
/**
 * Plugin Name
 *
 * @package           UnlikelyHtml2WxrConverter
 * @author            Doug Bierer
 * @copyright         2021 unlikelysource.com
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Unlikely HTML to WXR Converter
 * Plugin URI:        https://plugins.unlikelysource.biz
 * Description:       Converts stand alone HTML file(s) to WXR import file
 * Version:           1.0.0
 * Requires at least: 7.1
 * Requires PHP:      7.1
 * Author:            Doug Bierer
 * Author URI:        https://unlikelysource.com
 * Text Domain:       plugin-slug
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://plugins.unlikelysource.biz
 */

spl_autoload_register(function ($class) {
    if (strpos($class, 'Unlikely') === 0) {
        $fn = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        require __DIR__ . '/src/' . $fn;
    }
};
use Unlikely\HtmlWxrConvert\Admin;
add_action('admin_menu', 'unlikely_html2wxr_plugin_setup_menu');

function unlikely_html2wxr_plugin_setup_menu()
{
    add_menu_page('HTML to WXR Converter','HTML-WXR','manage_options','unlikely_html2wxr_plugin','unlikely_html2wxr_init');
}

function unlikely_html2wxr_init()
{
    echo Admin::USAGE;
}
