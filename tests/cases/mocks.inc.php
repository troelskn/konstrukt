<?php
class MockContext extends SimpleMock implements k_iContext
{
  public $registry;

  function __construct() {
    $this->registry = new k_Registry();
    $this->registry->set('GET', new ArrayObject(Array(), ArrayObject::ARRAY_AS_PROPS));
    $this->registry->set('POST', new ArrayObject(Array(), ArrayObject::ARRAY_AS_PROPS));
    $this->registry->set('ENV', new ArrayObject(Array('K_HTTP_METHOD' => 'GET'), ArrayObject::ARRAY_AS_PROPS));
    $this->registry->registerConstructor(':session',
      create_function(
        '$className, $args, $registry',
        'return $registry->get("MockSession");'
      )
    );
    $this->registry->registerAlias('session', ':session');
  }

  function getSubspace() {
    return "";
  }

  function getRegistry() {
    return $this->registry;
  }

  function url($href = "", $args = Array()) {
    return "";
  }
}

class MockContextWithFormValidation extends MockContext
{
  function validate() {
    return FALSE;
  }

  function validHandler() {}
}

class ExposedController extends k_Controller
{
  public $subspace = "";

  public function findNext() {
    return parent::findNext();
  }

  public function forward($name) {
    return parent::forward($name);
  }
}

class MockController extends ExposedController
{
  function handleRequest() {
    return "MockController";
  }
}

class MockGETController extends ExposedController
{
  public $calls = Array();

  function GET() {
    $this->calls[] = 'GET';
    return "MockGETController->GET";
  }

  function adaptResponse($response) {
    $this->calls[] = 'adaptResponse';
    return $response;
  }
}

class MockFormBehaviour extends k_FormBehaviour
{
  function getMemoryObject() {
    return $this->getMemory();
  }

  function render() {}
}

class MockSession
{
  protected $data = Array();

  function & get($identifyer) {
    if (!isset($this->data[$identifyer])) {
      $this->data[$identifyer] = Array();
    }
    return $this->data[$identifyer];
  }
}
