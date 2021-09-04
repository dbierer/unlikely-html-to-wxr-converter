<?php
namespace WP_CLI\UnlikelyTest;

use ArrayObject;
use WP_CLI\Unlikely\HtmlToWxrCommand;
use PHPUnit\Framework\TestCase;
class HtmlToWxrCommandTest extends TestCase
{
    public $command = NULL;
    public $container = NULL;
    public function setUp() : void
    {
        $this->command = new HtmlToWxrCommand();
        $this->container = new ArrayObject();
        $this->container->offsetSet('src', __DIR__ . '/../../data');
        $this->container->offsetSet('dest', __DIR__ . '/../../data');
        $this->container->offsetSet('ext', ['html']);
    }
    public function testGetDirIteratorReturnsIterable()
    {
        $iter = $this->command->getDirIterator($this->container);
        $expected = TRUE;
        $actual = is_iterable($iter);
        $this->assertEquals($expected, $actual, 'getDirIterator() does not return an iterable instance');
    }
    public function testGetDirIteratorFiltersNonHtml()
    {
        $iter = $this->command->getDirIterator($this->container);
        $expected = 0;
        $actual   = 0;
        foreach ($iter as $name => $obj)
            $actual += (bool) ($obj->getExtension() !== 'html');
        $this->assertEquals($expected, $actual, 'getDirIterator() does not filter out HTML');
    }
}
