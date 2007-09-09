<?php
require_once('../../examples/std.inc.php');

//////////////////////////////////////////////////////////////////////////////
class Controller_Default extends k_Controller
{
  function GET() {
    return "<h1>Default</h1><a href='".$this->context->url("foo")."'>Foo</a>";
  }
}

class Controller_Foo extends k_Controller
{
  protected function findNext() {
    return NULL;
  }

  function GET() {
    $session =& $this->registry->session->get(get_class($this));
    $response = "<h1>Controller_Foo</h1>\n<a href='".$this->top()."'>Default</a>\n"
        ."<p><a href='".$this->url()."'>Foo</a></p>\n"
        ."<p>subspace:".$this->getSubspace()."</p>\n"
        ."<p>Last-Request-Method:".@$session['Last-Request-Method']."</p>";
    $session['Last-Request-Method'] = "GET";
    return $response;
  }

  function POST() {
    $session =& $this->registry->session->get(get_class($this));
    $session['Last-Request-Method'] = "POST";
    throw new k_http_Redirect($this->url());
  }

  function PUT() {
    $session =& $this->registry->session->get(get_class($this));
    $session['Last-Request-Method'] = "PUT";
    throw new k_http_Redirect($this->url());
  }

  function DELETE() {
    $session =& $this->registry->session->get(get_class($this));
    $session['Last-Request-Method'] = "DELETE";
    throw new k_http_Redirect($this->url());
  }
}

class Controller_Bar extends k_Controller
{
  public $map = Array(
    'show' => 'Controller_Bar_Show'
  );

  protected function forward($name) {
    $response = parent::forward($name);
    return "<h1>Wrapped by Controller_Bar</h1>\n<div style='border:4px solid red'>".$response."</div>";
  }

  function execute() {
    throw new k_http_Redirect($this->url('show'));
  }
}

class Controller_Bar_Show extends k_Controller
{
  function GET() {
    return "Controller_Bar_Show";
  }
}

class Controller_Restricted extends k_Controller
{
  function GET() {
    if ($this->registry->identity instanceOf k_Anonymous) {
      throw new k_http_Authenticate("restricted");
    }
    return "<p>You are in the clear</p>";
  }
}

//////////////////////////////////////////////////////////////////////////////
class TestUser
{
  protected $username;
  protected $password;

  function __construct($username, $password) {
    $this->username = $username;
    $this->password = $password;
  }

  static function LoadUser($username, $password) {
    if ($username == "") {
      return new k_Anonymous();
    }
    return new TestUser($username, $password);
  }
}
//////////////////////////////////////////////////////////////////////////////

class Root extends k_Dispatcher
{
  public $map = Array(
    'default' => 'Controller_Default',
    'foo' => 'Controller_Foo',
    'bar' => 'Controller_Bar',
    'restricted' => 'Controller_Restricted',
  );
  function __construct() {
    parent::__construct();
    $this->userGateway = Array('TestUser','LoadUser');
  }
  function execute() {
    throw new k_http_Redirect($this->url('default'));
  }
}

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
