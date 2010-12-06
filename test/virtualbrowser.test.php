<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
require_once '../lib/konstrukt/konstrukt.inc.php';
require_once '../lib/konstrukt/virtualbrowser.inc.php';
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

class test_LoginForm extends k_Component {
  protected $message = "";
  function execute() {
    $this->url_state->init("destination", "http://www.example.org");
    return parent::execute();
  }
  function postForm() {
    if ($this->body('name') == 'Yoda' && $this->body('password') == 'TopNinja33') {
      return new k_SeeOther($this->query('destination'));
    }
    $this->message = 'You have failed';
    return $this->render();
  }
  function renderHtml() {
    return sprintf('
<form method="post" action="%s">
<p class="error">%s</p>
<p>
  <input type="text" name="name" value="%s" />
</p>
<p>
  <input type="password" name="password" />
</p>
<p>
  <input type="submit" id="login" />
</p>
</form>
', htmlspecialchars($this->url()), htmlspecialchars($this->message), htmlspecialchars($this->body('name', '')));
  }
}

class test_StatefulPage extends k_Component {
  function execute() {
    if ($this->query('setval')) {
      $this->session()->set('slot', $this->query('setval'));
    }
    return parent::execute();
  }
  function renderHtml() {
    return "value: " . htmlspecialchars($this->session('slot'))
      . "<br>"
      . "<a href='" . htmlspecialchars($this->url('', array('setval' => '42'))) . "'>setval</a>"
      ;
  }
}

class TestOfVirtualBrowser extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('test_LoginForm');
  }

  function test_requesting() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
  }

  function test_basic_form_interaction() {
    $this->get('?destination=void%3A%2F%2Fnowhere');
    $this->setField('name', 'Yoda');
    $this->setField('password', "I don't know");
    $this->clickSubmitById("login");
    $this->assertText("You have failed");
    $this->setField('password', "TopNinja33");
    $this->clickSubmitById("login");
    $this->assertText("void://nowhere");
  }
}

class TestOfVirtualBrowserSession extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('test_StatefulPage');
  }

  function test_requesting() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("setval");
  }

  function test_clicking_link_sets_persistent_slot() {
    $this->assertTrue($this->get('/'));
    $this->click("setval");
    $this->assertText("value: 42");
    $this->get('/');
    $this->assertText("value: 42");
  }
}

