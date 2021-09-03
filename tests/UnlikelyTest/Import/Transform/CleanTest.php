<?php
namespace WP_CLI\UnlikelyTest\Import\Transform;

use WP_CLI\Unlikely\Import\Transform\{Clean,TransformInterface};
use PHPUnit\Framework\TestCase;
class CleanTest extends TestCase
{
    public $clean = NULL;
    public function testImplementsTransformInterface()
    {
        $expected = TRUE;
        $clean = new Clean();
        $actual = ($clean instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testTidyExtensionAvailable()
    {
        $str = 'TEST';
        $expected = FALSE;
        $html = (new Clean())($str, ['bodyOnly' => FALSE]);
        $actual = ($html === $str);
        $this->assertEquals($expected, $actual, 'Tidy extension is not present');
    }
    public function testFullHtmlReturned()
    {
        $str = 'TEST';
        $search = '<!DOCTYPE html>';
        $expected = TRUE;
        $html = (new Clean())($str, ['bodyOnly' => FALSE]);
        $actual = (strpos($html, $search) === 0);
        $this->assertEquals($expected, $actual, 'Full HTML document not returned');
    }
    public function testOnlyBodyContentsReturned()
    {
        $str = 'TEST';
        $expected = $str;
        $actual = (new Clean())($str, ['bodyOnly' => TRUE]);
        $this->assertEquals($expected, $actual, 'Body contents not returned');
    }
    public function testStripsOffLFandSpaces()
    {
        $str = '<h1>Test</h1>' . "\n" . '<p>TEST</p>  ';
        $expected = '<h1>Test</h1><p>TEST</p>';
        $actual = (new Clean())($str);
        $this->assertEquals($expected, $actual, 'LF and leading/trailing spaces not removed.');
    }
}
