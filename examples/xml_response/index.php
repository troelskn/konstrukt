<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class Root extends k_Component {

  protected $content = "Waaaaaah! I feel good.";
  protected $html = "<html><head><title>Test</title><body><p id='content'>%s</p></body></html>";
  protected $xml  = "<content>%s</content>";

  function renderHtml() {
    return sprintf($this->html, $this->content);
  }

  function renderXml() {
    return new SimpleXMLElement(sprintf($this->xml, $this->content));
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('Root')->out();
}
