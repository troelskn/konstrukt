<?php
require_once 'konstrukt/konstrukt.inc.php';

class MyIdentityLoader extends k_BasicHttpIdentityLoader {
  function selectUser($username, $password) {
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

class Root extends k_Component {
  protected function map($name) {
    if ($name == "restricted") {
      return 'restrictedcontroller';
    }
  }
  function dispatch() {
    return sprintf("<html><body><h1>Authorization Example</h1>%s</body></html>", parent::dispatch());
  }
  function GET() {
    return sprintf("<p><a href='%s'>restricted</a></p><p>login with one of:</p><ul><li>pirate arrr</li><li>ninja supersecret</li></ul>", htmlspecialchars($this->url('restricted')));
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
  function GET() {
    return
      sprintf("<p>Hello %s, anon=%s</p>", htmlspecialchars($this->identity()->user()), $this->identity()->anonymous() ? 't' : 'f').
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
  function GET() {
    return "Welcome to the dojo, where only ninjas are allowed";
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->setIdentityLoader(new MyIdentityLoader())->run('Root')->out();
}
