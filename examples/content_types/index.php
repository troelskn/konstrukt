<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class MyMultiComponent extends k_Component {
  function renderHtml() {
    return "<html><title>html output</title><body><p>hello in html</p><p><a href='" . htmlspecialchars($this->url('/.json')) . "'>.json</a></p></body></html>";
  }
  function renderJson() {
    return array(
      'title' => 'content-type-json',
      'body' => 'hello in json');
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('MyMultiComponent')->out();
}
