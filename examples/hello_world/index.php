<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class Root extends k_Component {
  protected function map($name) {
    if ($name == "hello") {
      return 'hellocontroller';
    }
  }
  function dispatch() {
    return sprintf("<html><body><h1>Example 1</h1>%s</body></html>", parent::dispatch());
  }
  function GET() {
    return sprintf("<a href='%s'>say hello</a>", htmlspecialchars($this->url('hello')));
  }
}

class HelloController extends k_Component {
  function GET() {
    return "Hello World";
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('Root')->out();
}
