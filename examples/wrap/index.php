<?php
require_once '../../examples/std.inc.php';

class HelloWorld extends k_Controller
{
  function GET() {
    return "Hello World";
  }
}

class Wrap extends k_Controller
{
  public $map = Array('hello-world' => 'HelloWorld');
  function execute() {
    throw new k_http_Redirect($this->url('hello-world'));
  }
  function handleRequest() {
    return "<h1>wrap</h1>\n" . parent::handleRequest();
  }
}

class Root extends k_Dispatcher
{
  public $debug = TRUE;
  public $map = Array('wrap' => 'Wrap');

  function getSubspace() {
    return $this->context->getSubspace();
  }

  protected function findNext() {
    return 'wrap';
  }

  protected function forward($name) {
    if (!isset($this->map[$name])) {
      throw new k_http_Response(404);
    }
    $classname = $this->map[$name];
    if (!class_exists($classname)) {
      throw new k_http_Response(500);
    }
//     $this->forward = $name;
//     $next = new $classname($this, $name);
    $next = new $classname($this);
    return $next->handleRequest();
  }
}

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
