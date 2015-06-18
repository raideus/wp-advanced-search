<?php
namespace WPAS;
use WPAS\Enum\RequestMethod;
require_once(dirname(__DIR__) . '/wpas.php');

class TestHttpRequest extends \PHPUnit_Framework_TestCase {

    public function testCanGetValues() {
        $vars = array(
            'one' => 5,
            'two' => 'two',
            'three' => array('red','white','blue'),
            'four' => '<script>alert("Hello!")</script>'
        );

        $request = new HttpRequest($vars);

        $this->assertTrue($request->get('one') == "".$vars['one']);
        $this->assertTrue($request->get('two') == "".$vars['two']);
        $this->assertTrue($request->get('three') == $vars['three']);
    }

    public function testSanitizeScriptTags() {
        $vars = array(
            'four' => '<script>alert("Hello!")</script>'
        );

        $request = new HttpRequest($vars);

        $this->assertTrue($request->get('four') == "alert(&#34;Hello!&#34;)");
    }
}