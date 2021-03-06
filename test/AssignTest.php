<?php
/**
* Smarty PHPunit tests assign method
*
* @package PHPunit
* @author Uwe Tews
*/

namespace Box\Brainy\Tests;


class AssignTest extends Smarty_TestCase
{
    /**
    * test simple assign
    */
    public function testSimpleAssign() {
        $this->smarty->assign('foo', 'bar');
        $this->assertEquals('bar', $this->smarty->fetch('eval:{$foo}'));
    }
    /**
    * test assign array of variables
    */
    public function testArrayAssign() {
        $this->smarty->assign(array('foo'=>'bar', 'foo2'=>'bar2'));
        $this->assertEquals('bar bar2', $this->smarty->fetch('string:{$foo} {$foo2}'));
    }
}
