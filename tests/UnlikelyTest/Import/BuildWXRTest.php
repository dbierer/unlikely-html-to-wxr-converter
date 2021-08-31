<?php
namespace WP_CLI\UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Throwable;
use XmlWriter;
use SimpleXMLElement;
use WP_CLI\Unlikely\Import\{BuildWXR,Extract};
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
    public function testConstructItemConfigKeyFound()
    {
        $build   = new BuildWXR($this->extract, $this->config);
        $expected = FALSE;
        $actual   = (empty($build->item));
        $this->assertEquals($expected, $actual, 'Config key "item" not found');
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
    public function testBuildTemplateReturnsDOMDocument()
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
    public function testAddCallbackStoresObject()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $before = count($build->callbackManager);
        $build->addCallback(new DateTime());
        $after = count($build->callbackManager);
        $expected = TRUE;
        $actual = ($after > $before);
        $this->assertEquals($expected, $actual, 'CallbackManager did not store object');
    }
    public function testgetCallbackReturnsObject()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $build->addCallback(new DateTime());
        $expected = 'DateTime';
        $obj = $build->getCallback('DateTime');
        $actual = get_class($obj);
        $this->assertEquals($expected, $actual, 'CallbackManager did not return object');
    }
    public function testBuildWXRStoresExtractInstance()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $expected = Extract::class;
        $obj = $build->getCallback(Extract::class);
        $actual = get_class($obj);
        $this->assertEquals($expected, $actual, 'Extract instance not found in callbackManager');
    }
    public function testAddArticleReturnsDOMDocument()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $article = $build->addArticle([]);
        $expected = 'DOMDocument';
        $actual   = get_class($article);
        $this->assertEquals($expected, $actual, 'buildTemplate() does not create SimpleXMLElement instance');
    }
    public function testAddArticleAddsSingleTextNode()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $article = $build->addArticle(['test' => 'TEST']);
        $expected = 'TEST';
        $test = $article->getElementsByTagName('test')[0] ?? NULL;
        $actual = $test->textContent ?? '';
        $this->assertEquals($expected, $actual, 'Single text node not added');
    }
    public function testAddArticleAddsSingleTextCDataNode()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $article = $build->addArticle(['test' => ['CDATA' => 'TEST']]);
        $cdata = '<![CDATA[TEST]]>';
        $expected = TRUE;
        $actual = (bool) strpos($article->saveXML(), $cdata);
        $this->assertEquals($expected, $actual, 'Single CDATA text node not added');
    }
    public function testDoCallbackUsingFunction()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $params = ['callable' => 'strtoupper', 'args' => 'test'];
        $expected = 'TEST';
        $actual = $build->doCallback($params);
        $this->assertEquals($expected, $actual, 'Callable argument does not work');
    }
    public function testDoCallbackUsingAnonFunctionAndArrayArgs()
    {
        $build = new BuildWXR($this->extract, $this->config);
        $func = function (array $args) {
            $out = [];
            foreach ($args as $obj) $out[] = $obj->format('Y-m-d');
            return $out;
        };
        $params = ['callable' => $func, 'args' => [new DateTime('now'), new DateTime('tomorrow')]];
        $expected = date('Y-m-d');
        $actual = $build->doCallback($params)[0] ?? '';
        $this->assertEquals($expected, $actual, 'Anonymous function with array arguments does not work');
    }
    public function testDoCallbackUsingCallbackManager()
    {
        $this->config['ArrayObject'] = ['A','B','C'];
        $build = new BuildWXR($this->extract, $this->config);
        $params = ['class' => 'ArrayObject', 'method' => 'getArrayCopy'];
        $expected = ['A','B','C'];
        $actual = $build->doCallback($params);
        $this->assertEquals($expected, $actual, 'doCallback using callback class does not work');
    }
    public function testAddArticleRunsCallback()
    {
        $this->config['item'] = [
            'title' => ['CDATA' =>
                ['callback' => [
                    'class' => Extract::class,
                    'method' => 'getTitle']
                ]
            ],
        ];
        $build = new BuildWXR($this->extract, $this->config);
        $article = $build->addArticle([]);
        $expected = 'Chronic Mercury Poisoning: Symptoms &amp; Diseases';
        $test = $article->getElementsByTagName('title')[0] ?? NULL;
        $actual = $test->textContent ?? '';
        $this->assertEquals($expected, $actual, 'addArticles does not process callback correctly');
    }
}
