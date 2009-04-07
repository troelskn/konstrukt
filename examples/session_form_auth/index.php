<?php
require_once 'konstrukt/konstrukt.inc.php';

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
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function renderHtml() {
    throw new k_HttpResponse(
      401,
      "<html><head><title>Authentication required</title></head><body><form method='post' action='" . htmlspecialchars($this->url()) . "'>
  <h1>Authentication required</h1>
  <p>
    <label>
      username:
      <input type='text' name='username' />
    </label>
  </p>
  <p>
    <label>
      password:
      <input type='password' name='password' />
    </label>
  </p>
  <p>
    <input type='submit' value='Login' />
  </p>
</form></body></html>");
  }
  function postForm() {
    $user = $this->selectUser($this->body('username'), $this->body('password'));
    if ($user) {
      $this->session()->set('identity', $user);
      throw new k_SeeOther($this->query('continue'));
    }
    return $this->render();
  }
  protected function selectUser($username, $password) {
    $users = array(
      'ninja' => 'supersecret',
      'pirate' => 'arrr',
      'robot' => '*blip*',
    );
    if (isset($users[$username]) && $users[$username] == $password) {
      return new k_AuthenticatedUser($username);
    }
  }
}

class Logout extends k_Component {
  function execute() {
    $this->url_state->init("continue", $this->url('/'));
    return parent::execute();
  }
  function postForm() {
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
    return sprintf("<html><body><h1>Authentication Example</h1>%s</body></html>", parent::dispatch());
  }
  function renderHtml() {
    return sprintf(
      "<p><a href='%s'>restricted</a></p><p>login with one of:</p><ul><li>pirate arrr</li><li>ninja supersecret</li></ul>",
      htmlspecialchars($this->url('restricted')));
  }
}

class RestrictedController extends k_Component {
  protected function map($name) {
    if ($name == "dojo") {
      return 'dojocontroller';
    }
  }
  function dispatch() {
    if ($this->identity()->anonymous()) {
      throw new k_NotAuthorized();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    return
      sprintf("<p>Hello %s, anon=%s</p>", htmlspecialchars($this->identity()->user()), $this->identity()->anonymous() ? 't' : 'f') .
      sprintf("<form method='post' action='%s'><p><input type='submit' value='Log out' /></p></form>", htmlspecialchars($this->url('/logout'))) .
      sprintf("<p><a href='%s'>the dojo</a></p>", htmlspecialchars($this->url('dojo')));
  }
}

class DojoController extends k_Component {
  function dispatch() {
    if ($this->identity()->user() != 'ninja') {
      throw new k_Forbidden();
    }
    return parent::dispatch();
  }
  function renderHtml() {
    return "Welcome to the dojo, where only ninjas are allowed";
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
