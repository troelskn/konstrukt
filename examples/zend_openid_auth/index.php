<?php
require_once 'konstrukt/konstrukt.inc.php';

// You must have ZendFramework on your path
ini_set(
  'include_path',
  ini_get('include_path') . PATH_SEPARATOR . '/home/tkn/public/ZendFramework/library');

require_once 'Zend/Auth.php';
require_once 'Zend/Auth/Adapter/OpenId.php';
require_once 'Zend/Controller/Response/Abstract.php';
require_once 'Zend/Session.php';
// ZF is a monolith
Zend_Session::$_unitTestEnabled = true;

// http://localhost:1123/john.doe?openid.success=true

class ZfControllerResponseAdapter extends Zend_Controller_Response_Abstract {
  public function canSendHeaders($throw = false) {
    return true;
  }
  public function sendResponse() {
    $headers = $this->_headersRaw;
    foreach ($this->_headers as $header) {
      $headers[] = $header['name'] . ': ' . $header['value'];
    }
    throw new ZfThrowableResponse(
      $this->_httpResponseCode,
      implode("", $this->_body),
      $headers);
  }
}

class ZfThrowableResponse extends Exception {
  function __construct($status, $body, $headers) {
    $this->status = $status;
    $this->body = $body;
    $this->headers = $headers;
  }
  function status() {
    return $this->status;
  }
  function body() {
    return $this->body;
  }
  function headers() {
    return $this->headers;
  }
  function getRedirect() {
    foreach ($this->headers as $header) {
      if (preg_match('/^location: (.*)$/i', $header, $reg)) {
        return $reg[1];
      }
    }
  }
}

class k_SessionIdentityLoader implements k_IdentityLoader {
  function load(k_Context $context) {
    if ($context->session('identity')) {
      return $context->session('identity');
    }
    return new k_Anonymous();
  }
}

class NotAuthorizedComponent extends k_Component {
  function dispatch() {
    // redirect to login-page
    throw new k_TemporaryRedirect($this->url('/login', array('continue' => $this->requestUri())));
  }
}

class Login extends k_Component {
  protected $zend_auth;
  protected $errors;
  function __construct() {
    $this->zend_auth = Zend_Auth::getInstance();
    $this->errors = array();
  }
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function GET() {
    if ($this->query('openid_mode')) {
      $this->authenticate();
    }
    return parent::GET();
  }
  function postForm() {
    $this->authenticate();
    return $this->render();
  }
  function renderHtml() {
    throw new k_HttpResponse(
      401,
      "<html><head><title>Authentication required</title></head><body><form method='post' action='" . htmlspecialchars($this->url()) . "'>
  <h1>Authentication required</h1>
  <h2>OpenID Login</h2>
  <p>
" . implode("<br/>", $this->errors) . "
  </p>
  <p>
    <label>
      open-id url:
      <input type='text' name='openid_identifier' value='' />
    </label>
  </p>
  <p>
    <input type='submit' value='Login' />
  </p>
</form></body></html>");
  }
  protected function authenticate() {
    $open_id_adapter = new Zend_Auth_Adapter_OpenId($this->body('openid_identifier'));
    $open_id_adapter->setResponse(new ZfControllerResponseAdapter());
    try {
      $result = $this->zend_auth->authenticate($open_id_adapter);
    } catch (ZfThrowableResponse $response) {
      throw new k_SeeOther($response->getRedirect());
    }
    $this->errors = array();
    if ($result->isValid()) {
      $user = $this->selectUser($this->zend_auth->getIdentity());
      if ($user) {
        $this->session()->set('identity', $user);
        throw new k_SeeOther($this->query('continue'));
      }
      $this->errors[] = "Auth OK, but no such user on this system.";
    }
    $this->session()->set('identity', null);
    $this->zend_auth->clearIdentity();
    foreach ($result->getMessages() as $message) {
      $this->errors[] = htmlspecialchars($message);
    }
  }
  protected function selectUser($openid_identity) {
    return new k_AuthenticatedUser($openid_identity);
  }
}

class Logout extends k_Component {
  protected $zend_auth;
  function __construct() {
    $this->zend_auth = Zend_Auth::getInstance();
  }
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function postForm() {
    if ($this->zend_auth->hasIdentity()) {
      $this->zend_auth->clearIdentity();
    }
    $this->session()->set('identity', null);
    throw new k_SeeOther($this->query('continue'));
  }
}

class Root extends k_Component {
  protected function map($name) {
    switch ($name) {
    case 'restricted':
      return 'RestrictedController';
    case 'login':
      return 'Login';
    case 'logout':
      return 'Logout';
    }
  }
  function dispatch() {
    return sprintf("<html><body><h1>OpenID Authentication Example</h1>%s</body></html>", parent::dispatch());
  }
  function renderHtml() {
    return sprintf(
      "<p><a href='%s'>restricted</a></p>",
      htmlspecialchars($this->url('restricted')));
  }
}

class RestrictedController extends k_Component {
  function dispatch() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    return
      sprintf("<p>Hello %s, anon=%s</p>", htmlspecialchars($this->identity()->user()), $this->identity()->anonymous() ? 't' : 'f') .
      sprintf("<form method='post' action='%s'><p><input type='submit' value='Log out' /></p></form>", htmlspecialchars($this->url('/logout')));
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  $components = new k_DefaultComponentCreator();
  $components->setImplementation('k_DefaultNotAuthorizedComponent', 'NotAuthorizedComponent');
  $identity_loader = new k_SessionIdentityLoader();
  k()
    ->setComponentCreator($components)
    ->setIdentityLoader($identity_loader)
    ->run('Root')
    ->out();
}
