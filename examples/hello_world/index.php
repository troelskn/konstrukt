<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class Root extends k_Component {
  protected function map($name) {
    if ($name == "hello") {
      return 'hellocontroller';
    }
  }
  function execute() {
    return $this->wrap(parent::execute());
  }
  function wrapHtml($content) {
    return sprintf("<html><body><h1>Example 1</h1>%s</body></html>", $content);
  }
  function renderHtml() {
    return sprintf("<a href='%s'>say hello</a>", htmlspecialchars($this->url('hello')));
  }
}

class HelloController extends k_Component {
  function renderHtml() {
    return "Hello World";
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('Root')->out();
}
