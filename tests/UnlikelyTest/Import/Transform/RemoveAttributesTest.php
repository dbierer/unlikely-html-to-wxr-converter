<?php
namespace WP_CLI\UnlikelyTest\Import\Transform;

use WP_CLI\Unlikely\Import\Transform\{RemoveAttributes,TransformInterface};
use PHPUnit\Framework\TestCase;
class RemoveAttributesTest extends TestCase
{
    public $clean = NULL;
    public function testImplementsTransformInterface()
    {
        $expected = TRUE;
        $obj = new RemoveAttributes();
        $actual = ($obj instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testRemovesSingleAttribute()
    {
        $str = '<p style="margin-top: 0;">&nbsp;</p>';
        $params = ['attributes' => ['style']];
        $expected = '<p>&nbsp;</p>';
        $obj = new RemoveAttributes();
        $actual = $obj($str, $params);
        $this->assertEquals($expected, $actual, 'Single attribute not removed');
    }
    public function testRemovesMultipleAttributes()
    {
        $str = '<td width="150" height="20" background="../images/backgrounds/bkgnd_tandk.gif">';
        $params = ['attributes' => ['width', 'height']];
        $expected = '<td background="../images/backgrounds/bkgnd_tandk.gif">';
        $obj = new RemoveAttributes();
        $actual = $obj($str, $params);
        $this->assertEquals($expected, $actual, 'Multiple attributes not removed correctly');
    }
}
