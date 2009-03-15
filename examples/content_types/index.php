<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class MyMultiComponent extends k_Component {
  function renderHtml() {
    return "<html><title>html output</title><body><p>hello in html</p><p><a href='" . htmlspecialchars($this->url('/;json')) . "'>;json</a></p></body></html>";
  }
  function renderJson() {
    $response = new k_HttpResponse(200);
    $response->setContentType('application/json');
    $response->setContent("{ title: 'content-type-json', body: 'hello in json'}");
    throw $response;
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('MyMultiComponent')->out();
}
