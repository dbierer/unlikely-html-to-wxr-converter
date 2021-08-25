<?php
namespace UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Throwable;
use XmlWriter;
use SimpleXMLElement;
use Unlikely\Import\{BuildWXR,Extract};
use PHPUnit\Framework\TestCase;
class BuildWXRTest extends TestCase
{
    public $config = [];
    public $extract = NULL;
    public function setUp() : void
    {
        $this->config = include __DIR__ . '/../../../src/config/config.php';
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->extract = new Extract($fn, $this->config);
    }
    public function testConstructExportConfigKeyFound()
    {
        $build   = new BuildWXR($this->extract, $this->config);
        $expected = FALSE;
        $actual   = (empty($build->export));
        $this->assertEquals($expected, $actual, 'Config key "export" not found');
    }
    public function testConstructXmlWriterFound()
    {
        $build   = new BuildWXR($this->extract, $this->config);
        $expected = TRUE;
        $actual   = ($build->writer instanceof XmlWriter);
        $this->assertEquals($expected, $actual, 'XmlWriter not found');
    }
    public function testAddCdata()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $text  = 'test';
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><![CDATA[test]]></test>';
        $build->writer->openMemory();
        $build->writer->startDocument('1.0', 'UTF-8');
        $build->writer->startElement('test');
        $build->addCdata($text);
        $build->writer->endElement();
        $actual = $build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'CDATA not built properly');
    }
    public function testAddNodeSingle()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $node  = ['test' => 'TEST'];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test>TEST</test>';
        $build->addNode($node);
        $actual = $build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Single node not built correctly');
    }
    public function testAddNodeNested()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $node  = ['test' => ['child' => 'TEST']];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><child>TEST</child></test>';
        $build->addNode($node);
        $actual = $build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Nested nodes not built correctly');
    }
    public function testAddNodeNestedWithCdata()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $node  = ['test' => ['child' => ['CDATA' => 'TEST']]];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><child><![CDATA[TEST]]></child></test>';
        $build->addNode($node);
        $actual = $build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Nested nodes with CDATA not built correctly');
    }
    public function testBuildTemplate()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $build->buildTemplate();
        $expected = 'DOMDocument';
        $actual   = get_class($build->template);
        $this->assertEquals($expected, $actual, 'buildTemplate() does not create SimpleXMLElement instance');
    }
    public function testBuildTemplateAddsRssNode()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $build->buildTemplate();
        $expected = TRUE;
        $actual   = strpos($build->template->saveXML(), '</rss>');
        $this->assertEquals($expected, $actual, 'buildTemplate() does not create RSS node');
    }
    public function testBuildTemplateAddsChannelNode()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $build->buildTemplate();
        $expected = TRUE;
        $actual   = strpos($build->template->saveXML(), '</channel>');
        $this->assertEquals($expected, $actual, 'buildTemplate() does not create "channel" node');
    }
    public function testAddArticle()
    {
        $expected = TRUE;
        $actual   = FALSE;
        $this->assertEquals($expected, $actual, 'addArticle() not working');
    }
}
