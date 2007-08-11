<?php
require_once('../../examples/std.inc.php');

class IndexPage extends k_Controller
{
  protected $form;

  function __construct(k_iContext $context, $uri) {
    parent::__construct($context, $uri);
    $this->form = new k_FormBehaviour($this, "index.template.php");
    $this->form->descriptors = Array(
      Array("name" => "name"),
      Array("name" => "email", "filters" => Array("trim", "strtolower")),
      Array("name" => "email_again"),
      Array("name" => "age"),
      Array("name" => "foo", "default" => "foo-default", "filters" => Array("trim", "strtolower")),
    );
  }

  function execute() {
    return $this->form->execute();
  }

  function validHandler($fields) {
    return "<h1>valid</h1>\n<pre>".var_export($fields, TRUE)."</pre>";
  }

  function validate($values) {
    $validator = $this->form->getValidator();
    $validator->assertNumeric("age");
    $validator->assertEmail("email");
    $validator->assertEqual("email", "email_again", "emails doesn't match");
    return $validator->isValid();
  }
}

class Root extends k_Dispatcher
{
  public $map = Array('default' => 'IndexPage');
  function execute() {
    throw new k_http_Redirect($this->url('default'));
  }
}

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
