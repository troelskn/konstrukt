<?php
class TemplateFactory {
  protected $template_dir;
  protected $compile_dir;
  function __construct($template_dir, $compile_dir) {
    $this->template_dir = $template_dir;
    $this->compile_dir = $compile_dir;
  }
  function create() {
    $smarty = new Smarty();
    $smarty->template_dir = $this->template_dir;
    $smarty->compile_dir = $this->compile_dir;
    return $smarty;
  }
}