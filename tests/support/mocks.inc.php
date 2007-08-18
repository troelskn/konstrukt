<?php
if (!class_exists('SimplePutEncoding')) {
	class SimplePutEncoding extends SimplePostEncoding {
		function getMethod() {
			return 'PUT';
		}
	}
}
if (!class_exists('SimpleDeleteEncoding')) {
	class SimpleDeleteEncoding extends SimpleGetEncoding {
		function getMethod() {
			return 'DELETE';
		}
	}
}

class ExtendedWebTestCase extends WebTestCase
{
	function ExtendedWebTestCase() {
		$this->WebTestCase();
		$this->_baseUrl = $GLOBALS['simpletest_configuration']['test_server_url'];
	}

	function request($method, $url, $parameters = FALSE) {
		if (!is_object($url)) {
			$url = new SimpleUrl($url);
		}
		if ($this->getUrl()) {
			$url = $url->makeAbsolute($this->_browser->getUrl());
		}
		switch (strtoupper($method)) {
			case 'GET' : $encoding = new SimpleGetEncoding($parameters);
			break;
			case 'POST' : $encoding = new SimplePostEncoding($parameters);
			break;
			case 'PUT' : $encoding = new SimplePutEncoding($parameters);
			break;
			case 'DELETE' : $encoding = new SimpleDeleteEncoding($parameters);
			break;
			default :
				$this->fail("Unknown method '$method'");
				return;
		}
		return $this->_browser->_load($url, $encoding);
	}
}

class MockContext extends SimpleMock implements k_iContext
{
  protected $urlBuilder;
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
    $this->urlBuilder = new k_UrlBuilder("test://example.org/");
  }

  function getSubspace() {
    return "";
  }

  function getRegistry() {
    return $this->registry;
  }

  function url($href = "", $args = Array()) {
    return $this->urlBuilder->url($href, $args);
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
