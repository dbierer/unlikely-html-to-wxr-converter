<?php
namespace WP_CLI\UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use WP_CLI\Unlikely\Import\Extract;
use PHPUnit\Framework\TestCase;
class ExtractTest extends TestCase
{
    public $config;
    public function setUp() : void
    {
        $this->config = include __DIR__ . '/../../../src/config/config.php';
    }
    public function testConstructConfigurationFileLoaded()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = FALSE;
        $actual   = (empty($extract->config));
        $this->assertEquals($expected, $actual, 'Config file not loaded');
    }
    public function testConstructHtmlFileNotFound()
    {
        //$this->expectException(Exception::class);
        $err = [];
        $fn  = 'file_does_not_exist.html';
        $expected = 'Exception';
        try {
            $extract = new Extract($fn, $this->config);
            $actual = $extract->getHtml($err);
            error_log(__METHOD__ . ':' . var_export($err, TRUE));
        } catch (Throwable $t) {
            $actual = get_class($t);
        }
        $this->assertEquals($expected, $actual, 'Does not handle file not found properly');
    }
    public function testResetFile()
    {
        $fn1  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn1, $this->config);
        $size1 = strlen($extract->contents);
        $fn2  = __DIR__ . '/../../../data/find_health_pros.html';
        $extract->resetFile($fn2);
        $size2 = strlen($extract->contents);
        $this->assertNotEquals($size1, $size2, 'resetFile() not working');
    }
    public function testGetContents()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = filesize($fn);
        $size     = strlen($extract->contents);
        $min      = $size - ($expected * .9);
        $max      = $size + ($expected * .9);
        $actual   = ($min < $size && $size < $max);
        $this->assertEquals($expected, $actual, 'Actual content length not within 10% of expected');
    }
    public function testGetContentsDoesNotContainLF()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = FALSE;
        $actual   = strpos($extract->contents, "\n");
        $this->assertEquals($expected, $actual, 'Content contains LF');
    }
    public function testGetCreateDate()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $fdate = new DateTime('@' . filectime($fn));
        $fdate->setTimeZone(new DateTimeZone('PST'));
        $extract = new Extract($fn, $this->config);
        $expected = $fdate->format(DATE_RSS);
        $actual   = $extract->getCreateDate();
        $this->assertEquals($expected, $actual, 'Create date does not match');
    }
    public function testGetCreateDateUtc()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $date1 = $extract->getCreateDate();
        $date2 = $extract->getCreateDate('UTC');
        $expected = FALSE;
        $actual   = ($date1 == $date2);
        $this->assertEquals($expected, $actual, 'Create PST and UTC dates should not match');
    }
    public function testGetLastDir()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = 'data';
        $actual   = $extract->getLastDir();
        $this->assertEquals($expected, $actual, 'Incorrect immediate directory name');
    }
    public function testGetWpFilename()
    {
        $fn  = __DIR__ . '/../../../data/find_health_pros.html';
        $extract = new Extract($fn, $this->config);
        $expected = 'find-health-pros';
        $actual   = $extract->getWpfilename();
        $this->assertEquals($expected, $actual, 'Incorrect WP filename returned');
    }
    public function testGetWpLink()
    {
        $fn  = __DIR__ . '/../../../data/find_health_pros.html';
        $url = 'https://mercurysafeandmercuryfree.com';
        $extract = new Extract($fn, $this->config);
        $expected = $url . '/data/find-health-pros';
        $actual   = $extract->getWpLink($url);
        $this->assertEquals($expected, $actual, 'Incorrect WP link returned');
    }
    public function testGetWpLinkTrailingSlash()
    {
        $fn  = __DIR__ . '/../../../data/find_health_pros.html';
        $url = 'https://mercurysafeandmercuryfree.com/';
        $extract = new Extract($fn, $this->config);
        $expected = $url . 'data/find-health-pros';
        $actual   = $extract->getWpLink($url);
        $this->assertEquals($expected, $actual, 'Incorrect WP link returned');
    }
    public function testGetTitle()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = 'Chronic Mercury Poisoning: Symptoms &amp; Diseases';
        $actual   = $extract->getTitle();
        $this->assertEquals($expected, $actual, 'Title not extracted correctly');
    }
    public function testGetExcerptOneTag()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->config[Extract::class]['excerpt_tags'] = ['h2' => 'h2'];
        $extract = new Extract($fn, $this->config);
        $expected = 'Mercury Poisoning: Symptoms and Diseases';
        $actual   = $extract->getExcerpt();
        $this->assertEquals($expected, $actual, 'Single tag excerpt not extracted correctly');
    }
    public function testGetExcerptTwoTags()
    {
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->config[Extract::class]['excerpt_tags'] = ['h2' => 'p', 'p' => 'p'];
        $extract = new Extract($fn, $this->config);
        $expected = 'Mercury is the most poisonous, non-radioactive, naturally occurring substance on our planet.';
        $actual   = substr($extract->getExcerpt(), 0, strlen($expected));
        $this->assertEquals($expected, $actual, 'Double tag excerpt not extracted correctly');
    }
    public function testGetHtmlFileNotFoundErrorMessageOK()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms_missing_delim.html';
        $extract = new Extract($fn, $this->config);
        $expected = Extract::ERR_DELIM;
        $html = $extract->getHtml($err);
        $actual = $err[0] ?? '';
        $this->assertEquals($expected, $actual, 'File not found error does not appear');
    }
    public function testGetHtmlAttribsRemovedProperly()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = FALSE;
        $html = $extract->getHtml($err);
        $actual = strpos($html, 'width=');
        $this->assertEquals($expected, $actual, 'Width attribute not removed');
    }
    public function testGetHtmlBlockRemovedProperly()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $extract = new Extract($fn, $this->config);
        $expected = FALSE;
        $html = $extract->getHtml($err);
        $actual = strpos($html, '<tr height="20">');
        $this->assertEquals($expected, $actual, 'Block not removed properly');
    }
    public function testGetHtmlThrowsExceptionInvalidCallback()
    {
        $err = [];
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->config[Extract::class]['transform'] = ['test' => ['callback' => 'XYZ', 'params' => []]];
        $expected = 'Exception';
        try {
            $extract = new Extract($fn, $this->config);
            $actual = $extract->getHtml($err);
            error_log(__METHOD__ . ':' . var_export($err, TRUE));
        } catch (Throwable $t) {
            $actual = get_class($t);
        }
        $this->assertEquals($expected, $actual, 'Exception not thrown if transform callback is invalid ');
    }
}
