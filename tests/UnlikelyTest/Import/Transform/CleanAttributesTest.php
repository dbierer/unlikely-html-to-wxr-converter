<?php
namespace WP_CLI\UnlikelyTest\Import\Transform;

use WP_CLI\Unlikely\Import\Transform\{CleanAttributes,TransformInterface};
use PHPUnit\Framework\TestCase;
class CleanAttributesTest extends TestCase
{
    public $clean = NULL;
    public function testImplementsTransformInterface()
    {
        $expected = TRUE;
        $obj = new CleanAttributes();
        $actual = ($obj instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testCleansSingleAttribute()
    {
        $str = '<p ' . "\n" . 'style="margin-top: 0;">&nbsp;</p>';
        $params = ['attributes' => ['style']];
        $expected = '<p style="margin-top: 0;">&nbsp;</p>';
        $obj = new CleanAttributes();
        $actual = $obj($str, $params);
        $this->assertEquals($expected, $actual, 'Single attribute not removed');
    }
}
