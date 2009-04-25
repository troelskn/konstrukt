<?php
class components_Root extends k_Component {
  protected $templates;
  function __construct(TemplateFactory $templates) {
    $this->templates = $templates;
  }
  function execute() {
    return $this->wrap(parent::execute());
  }
  function wrapHtml($content) {
    $smarty = $this->templates->create();
    $smarty->assign('content', $content);
    $smarty->assign('title', $this->document->title());
    $smarty->assign('scripts', $this->document->scripts());
    $smarty->assign('styles', $this->document->styles());
    $smarty->assign('onload', $this->document->onload());
    return $smarty->fetch("document.tpl");
  }
  function renderHtml() {
    $smarty = $this->templates->create();
    return $smarty->fetch("root.tpl");
  }
}
