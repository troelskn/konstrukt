<?php
class components_Root extends k_Component {
  function execute() {
    return $this->wrap(parent::execute());
  }
  function wrapHtml($content) {
    $t = new k_Template("templates/document.tpl.php");
    return
      $t->render(
        $this,
        array(
          'content' => $content,
          'title' => $this->document->title(),
          'scripts' => $this->document->scripts(),
          'styles' => $this->document->styles(),
          'onload' => $this->document->onload()));
  }
  function renderHtml() {
    $t = new k_Template("templates/root.tpl.php");
    return $t->render($this);
  }
}
