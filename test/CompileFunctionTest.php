<?php
/**
* Smarty PHPunit tests compilation of {function} tag
*
* @package PHPunit
* @author Uwe Tews
*/

/**
* class for {function} tag tests
*/
class CompileFunctionTest extends PHPUnit_Framework_TestCase
{
    public function setUp() {
        $this->smarty = SmartyTests::$smarty;
        SmartyTests::init();
    }

    /**
    * test simple function call tag
    */
    public function testSimpleFunction() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag1.tpl');
        $this->assertEquals("default param", $this->smarty->fetch($tpl));
    }
    /**
    * test simple function call tag 2
    */
    public function testSimpleFunction2() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag2.tpl');
        $this->assertEquals("default param default param2", $this->smarty->fetch($tpl));
    }
    /**
    * test overwrite default function call tag
    */
    public function testOverwriteDefaultFunction() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag3.tpl');
        $this->assertEquals("overwrite param default param2", $this->smarty->fetch($tpl));
    }
    /**
    * test recursive function call tag
    */
    public function testRecursiveFunction() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag4.tpl');
        $this->assertEquals("012345", $this->smarty->fetch($tpl));
    }
    /**
    * test inherited function call tag
    */
    public function testInheritedFunction() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag5.tpl');
        $this->assertEquals("012345", $this->smarty->fetch($tpl));
    }
    /**
    * test function definition in include
    */
    public function testDefineFunctionInclude() {
        $tpl = $this->smarty->createTemplate('test_template_function_tag6.tpl');
        $this->assertEquals("012345", $this->smarty->fetch($tpl));
    }
    /**
    * test external function definition
    */
    public function testExternalDefinedFunction() {
        $tpl = $this->smarty->createTemplate('eval:{include file=\'template_function_lib.tpl\'}{call name=template_func1}');
        $tpl->assign('foo', 'foo');
        $this->assertContains('foo', $this->smarty->fetch($tpl));
    }
}
