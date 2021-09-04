<?php
namespace WP_CLI\UnlikelyTest\Import;

use DateTime;
use DateTimeZone;
use Throwable;
use UnexpectedValueException;
use XmlWriter;
use SimpleXMLElement;
use WP_CLI\Unlikely\Import\{BuildWXR,Extract,BuildWXRInterface};
use PHPUnit\Framework\TestCase;
class BuildWXRTest extends TestCase
{
    public $config = [];
    public $extract = NULL;
    public $build = NULL;
    public $mock_callback = NULL;
    public function setUp() : void
    {
        $this->config = include __DIR__ . '/../../../src/config/config.php';
        $fn  = __DIR__ . '/../../../data/symptoms.html';
        $this->extract = new Extract($fn, $this->config);
        $this->build   = new BuildWXR($this->config, $this->extract);
        $this->mock_callback = new class () extends DateTime implements BuildWXRInterface {
            public $build = NULL;
            public function setBuildWXRInstance(BuildWXR $build)
            {
                $this->build = $build;
            }
        };
    }
    public function testConstructExportConfigKeyFound()
    {
        $expected = FALSE;
        $actual   = (empty($this->build->export));
        $this->assertEquals($expected, $actual, 'Config key "export" not found');
    }
    public function testConstructItemConfigKeyFound()
    {
        $expected = FALSE;
        $actual   = (empty($this->build->item));
        $this->assertEquals($expected, $actual, 'Config key "item" not found');
    }
    public function testConstructXmlWriterFound()
    {
        $expected = TRUE;
        $actual   = ($this->build->writer instanceof XmlWriter);
        $this->assertEquals($expected, $actual, 'XmlWriter not found');
    }
    public function testAddCdata()
    {
        $text  = 'test';
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><![CDATA[test]]></test>';
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('test');
        $this->build->addCdata($text);
        $this->build->writer->endElement();
        $actual = $this->build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'CDATA not built properly');
    }
    public function testAddNodeSingle()
    {
        $node  = ['test' => 'TEST'];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test>TEST</test>';
        $this->build->addNode($node);
        $actual = $this->build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Single node not built correctly');
    }
    public function testAddNodeNested()
    {
        $node  = ['test' => ['child' => 'TEST']];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><child>TEST</child></test>';
        $this->build->addNode($node);
        $actual = $this->build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Nested nodes not built correctly');
    }
    public function testAddNodeNestedWithCdata()
    {
        $node  = ['test' => ['child' => ['CDATA' => 'TEST']]];
        $expected = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<test><child><![CDATA[TEST]]></child></test>';
        $this->build->addNode($node);
        $actual = $this->build->writer->outputMemory();
        $this->assertEquals($expected, $actual, 'Nested nodes with CDATA not built correctly');
    }
    public function testAddCallbackStoresObject()
    {
        $before = count($this->build->callbackManager);
        $this->build->addCallback($this->mock_callback);
        $after = count($this->build->callbackManager);
        $expected = TRUE;
        $actual = ($after > $before);
        $this->assertEquals($expected, $actual, 'CallbackManager did not store object');
    }
    public function testaddCallbackThrowsInvalidArgumentExceptionIfCallbackDoesntImplementBuildWXRInterface()
    {
        try {
            $this->build->addCallback(new DateTime());
            $actual = 'DateTime';
        } catch (Throwable $t) {
            $actual = get_class($t);
        }
        $expected = 'InvalidArgumentException';
        $this->assertEquals($expected, $actual, 'addCallback does not throw InvalidArgumentException');
    }
    public function testGetCallbackReturnsNullIfCallbackDoesntExist()
    {
        $expected = NULL;
        $actual = $this->build->getCallback('ArrayObject');
        $this->assertEquals($expected, $actual, 'getCallback() does not return NULL if callback not registered');
    }
    public function testGetCallbackDoesNotReportEmptyIfCallbackDoesntExist()
    {
        $expected = TRUE;
        $result   = $this->build->getCallback('ArrayObject');
        $actual   = empty($result);
        $this->assertEquals($expected, $actual, 'getCallback() does not report empty if callback not registered');
    }
    public function testBuildWXRStoresExtractInstance()
    {
        $expected = Extract::class;
        $obj = $this->build->getCallback(Extract::class);
        $actual = (empty($obj)) ? NULL : get_class($obj);
        $this->assertEquals($expected, $actual, 'Extract instance not found in callbackManager');
    }
    public function testAddArticleReturnsXML()
    {
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle([]);
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $expected = '<?xml version="1.0" encoding="UTF-8"?>';
        $actual   = substr($article, 0, 38);
        $this->assertEquals($expected, $actual, 'buildTemplate() does not create XML');
    }
    public function testAddArticleAddsSingleTextNode()
    {
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle(['test' => 'TEST']);
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $search  = '<test>TEST</test>';
        $expected = TRUE;
        $actual   = (bool) strpos($article, $search);
        $this->assertEquals($expected, $actual, 'Single text node not added');
    }
    public function testAddArticleAddsSingleTextCDataNode()
    {
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle(['test' => ['CDATA' => 'TEST']]);
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $cdata = '<![CDATA[TEST]]>';
        $expected = TRUE;
        $actual = (bool) strpos($article, $cdata);
        $this->assertEquals($expected, $actual, 'Single CDATA text node not added');
    }
    public function testDoCallbackUsingFunction()
    {
        $params = ['callable' => 'strtoupper', 'args' => 'test'];
        $expected = 'TEST';
        $actual = $this->build->doCallback($params);
        $this->assertEquals($expected, $actual, 'Callable argument does not work');
    }
    public function testDoCallbackUsingAnonFunctionAndArrayArgs()
    {
        $func = function (array $args) {
            $out = [];
            foreach ($args as $obj) $out[] = $obj->format('Y-m-d');
            return $out;
        };
        $params = ['callable' => $func, 'args' => [new DateTime('now'), new DateTime('tomorrow')]];
        $expected = date('Y-m-d');
        $actual = $this->build->doCallback($params)[0] ?? '';
        $this->assertEquals($expected, $actual, 'Anonymous function with array arguments does not work');
    }
    public function testUseCallbackManager()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format', 'args' => 'Y-m-d'];
        $expected = date('Y-m-d');
        $actual = $this->build->useCallbackManager($params);
        $this->assertEquals($expected, $actual, 'useCallbackManager using callback class does not work');
    }
    public function testUseCallbackManagerThrowsBadMethodCallExceptionIfMethodDoesntExist()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'xyz'];
        $expected = 'BadMethodCallException';
        try {
            $actual = $this->build->useCallbackManager($params);
        } catch (Throwable   $e) {
            $actual = get_class($e);
        }
        $this->assertEquals($expected, $actual, 'useCallbackManager does not throw BadMethodCallException if method does not exist');
    }
    public function testUseCallbackManagerThrowsExceptionIfNoConfig()
    {
        $class = get_class($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format'];
        $expected = 'Exception';
        try {
            $actual = $this->build->useCallbackManager($params);
        } catch (Throwable   $e) {
            $actual = get_class($e);
        }
        $this->assertEquals($expected, $actual, 'useCallbackManager does not throw Exception if config not found');
    }
    public function testDoCallbackUsingCallbackManager()
    {
        $class = get_class($this->mock_callback);
        $this->build->config[$class] = ['now', new DateTimeZone('UTC')];
        $this->build->addCallback($this->mock_callback);
        $params = ['class' => $class, 'method' => 'format', 'args' => 'Y-m-d'];
        $expected = date('Y-m-d');
        $actual = $this->build->doCallback($params);
        $this->assertEquals($expected, $actual, 'doCallback using callback class does not work');
    }
    public function testAddArticleRunsCallback()
    {
        $item = [
            'title' =>
                ['callback' => [
                    'class' => Extract::class,
                    'method' => 'getTitle']
                ]
        ];
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle($item);
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $search = '<title>Chronic Mercury Poisoning: Symptoms &amp;amp; Diseases</title>';
        $expected = TRUE;
        $actual = (bool) strpos($article, $search);
        $this->assertEquals($expected, $actual, 'addArticle() does not process callback correctly');
    }
    public function testAddArticleCDATARunsCallback()
    {
        $item = [
            'title' => ['CDATA' =>
                ['callback' => [
                    'class' => Extract::class,
                    'method' => 'getTitle']
                ]
            ],
        ];
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle($item);
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $search  = '<![CDATA[Chronic Mercury Poisoning: Symptoms &amp; Diseases]]>';
        $expected = TRUE;
        $actual = (bool) strpos($article, $search);
        $this->assertEquals($expected, $actual, 'addArticle() does not process callback producing CDATA correctly');
    }
    public function testAddArticleOnActualDocument()
    {
        $this->build->writer->openMemory();
        $this->build->writer->startDocument('1.0', 'UTF-8');
        $this->build->writer->startElement('item');
        $this->build->addArticle();
        $this->build->writer->endElement();
        $article = $this->build->writer->outputMemory();
        $expected = '<category domain="category" nicename="data"><![CDATA[data]]></category></item>';
        $pos = -1 * strlen($expected);
        $actual = substr($article, $pos);
        $this->assertEquals($expected, $actual, 'addArticle() does not work on full document');
    }
    public function testbuildWxrReturnsSimpleXMLElement()
    {
        $wxr = $this->build->buildWxr('', TRUE);
        $expected = 'SimpleXMLElement';
        $actual   = get_class($wxr);
        $this->assertEquals($expected, $actual, 'buildWxr() does not create SimpleXMLElement instance');
    }
    public function testBuildWxrAddsRssNode()
    {
        $wxr = $this->build->buildWxr('', TRUE);
        $expected = TRUE;
        $actual   = strpos($wxr->asXML(), '</rss>');
        $this->assertEquals($expected, $actual, 'buildWxr() does not create RSS node');
    }
    public function testBuildWxrAddsChannelNode()
    {
        $wxr = $this->build->buildWxr('', TRUE);
        $expected = TRUE;
        $actual   = strpos($wxr->asXML(), '</channel>');
        $this->assertEquals($expected, $actual, 'buildWxr() does not create "channel" node');
    }
    public function testBuildWxrAddsItemNode()
    {
        $wxr = $this->build->buildWxr();
        $expected = TRUE;
        $actual   = strpos($wxr->asXML(), '</item>');
        $this->assertEquals($expected, $actual, 'buildWxr() does not create "item" node');
    }
    public function testBuildWxrWritesXMLFile()
    {
        $fn = '/tmp/test.xml';
        if (file_exists($fn)) unlink($fn);
        $wxr = $this->build->buildWxr($fn);
        $expected = TRUE;
        $actual   = file_exists($fn);
        $this->assertEquals($expected, $actual, 'buildWxr() did not write XML file');
    }
}
