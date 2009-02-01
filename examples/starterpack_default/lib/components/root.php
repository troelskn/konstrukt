<?php
class components_Root extends k_Component {
  protected $templates;
  function __construct(TemplateFactory $templates) {
    $this->templates = $templates;
  }
  function dispatch() {
    $smarty = $this->templates->create();
    $smarty->assign('content', parent::dispatch());
    $smarty->assign('title', $this->document->title());
    $smarty->assign('scripts', $this->document->scripts());
    $smarty->assign('styles', $this->document->styles());
    $smarty->assign('onload', $this->document->onload());
    return $smarty->fetch("document.tpl");
  }
  function GET() {
    $smarty = $this->templates->create();
    return $smarty->fetch("root.tpl");
  }
}
