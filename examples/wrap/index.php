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

  function execute() {
    throw new k_http_Redirect($this->url('wrap'));
  }
}

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
