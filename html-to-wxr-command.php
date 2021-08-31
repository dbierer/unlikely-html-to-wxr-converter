<?php
namespace WP_CLI\Unlikely;
use WP_CLI;
use WP_CLI\Unlikely\HtmlToWxrCommand;
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}
require __DIR__ . '/vendor/autoload.php';
WP_CLI::add_command(
    'html-to-wxr',
    HtmlToWxrCommand::class,
    HtmlToWxrCommand::SYNOPSIS
);
