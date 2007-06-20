<?php
class Root extends k_Dispatcher
{
  public $debug = TRUE;
  public $map = Array(
    'blog' => 'Controller_Blog_Index',
  );

  function __construct() {
    parent::__construct();
    $this->document->template = "../templates/document.tpl.php";
    $this->document->title = "Sample Blog";
    $this->document->styles[] = $this->url("/res/style.css");
  }

  function execute() {
    return $this->forward('blog');
  }
}
