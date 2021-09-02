<?php
namespace WP_CLI\UnlikelyTest\Import\Transform;

use WP_CLI\Unlikely\Import\Transform\{RemoveBlock,TransformInterface};
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
class RemoveBlockTest extends TestCase
{
    public $transform = NULL;
    public function testImplementsTransformInterface()
    {
        $transform = new RemoveBlock();
        $expected = TRUE;
        $actual = ($transform instanceof TransformInterface);
        $this->assertEquals($expected, $actual, 'Class does not implement TransformInterface');
    }
    public function testMagicInvoke()
    {
        $transform = new RemoveBlock();
        $expected = TRUE;
        $actual   = is_callable($transform);
        $this->assertEquals($expected, $actual, 'Class is not callable');
    }
    public function testInitThrowsInvalidArgumentExceptionIfParamsMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        $transform = new RemoveBlock();
        $expected = 'InvalidArgumentException';
        $missing = ['start' => '<p>', 'stop' => '</p>'];
        $html = '<p>TEST</p>';
        try {
            $transform->init($missing);
        } catch (Throwable $t) {
            $result = get_class($t);
        }
        $actual = (is_object($result) && ($result instanceof InvalidArgumentException));
        $this->assertEquals($expected, $actual, 'InvalidArgumentException not thrown if missing param');
    }
    public function testGetStartAndStopReturnsTrueWhenStartAndStopFound()
    {
        $transform = new RemoveBlock();
        $html = '<h1>TEST</h1><p>TEST</p>';
        $params = ['start' => '<h1>', 'stop' => '</h1>', 'items' => ['TEST']];
        $transform->init($params);
        $expected = TRUE;
        $actual = $transform->getStartAndStop($html);
        $this->assertEquals($expected, $actual, 'Does not return true when start and stop found');
    }
    public function testGetStartAndStopIdentifiesCorrectPositions()
    {
        $transform = new RemoveBlock();
        $html = '<h1>TEST</h1><h2>TEST</h2><p>TEST</p>';
        $params = ['start' => '<h2>', 'stop' => '</h2>', 'items' => ['TEST']];
        $transform->init($params);
        $expected = [13, 21];
        $transform->getStartAndStop($html);
        $actual = [$transform->beg_pos, $transform->end_pos];
        $this->assertEquals($expected, $actual, 'Does not identifiy correct begin/end positions');
    }
    public function testConfirmIdentifiesInternalItems()
    {
        $transform = new RemoveBlock();
        $html = '<h1>TEST</h1><h2>TEST ONE TWO THREE</h2><p>TEST</p>';
        $params = ['start' => '<h2>', 'stop' => '</h2>', 'items' => ['ONE','TWO','THREE']];
        $transform->init($params);
        $expected = TRUE;
        $transform->getStartAndStop($html);
        $actual = $transform->confirm($html);
        $this->assertEquals($expected, $actual, 'Does not confirm internal items');
    }
    public function testRemove()
    {
        $transform = new RemoveBlock();
        $html = '<h1>TEST</h1><h2>TEST ONE TWO THREE</h2><p>TEST</p>';
        $params = ['start' => '<h2>', 'stop' => '</h2>', 'items' => ['ONE','TWO','THREE']];
        $transform->init($params);
        $expected = TRUE;
        $transform->getStartAndStop($html);
        $transform->confirm($html);
        $expected = '<h1>TEST</h1><p>TEST</p>';
        $actual = $transform->remove($html);
        $this->assertEquals($expected, $actual, 'Does not remove identified block');
    }
}
