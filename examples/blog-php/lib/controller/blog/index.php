<?php
class Controller_Blog_Index extends k_Controller
{
  function __construct(k_iContext $context, $name = "", $urlNamespace = "blog") {
    parent::__construct($context, $name, $urlNamespace);
  }

  protected function initializeState() {
    $this->state->initialize(
      Array(
        'offset' => 0));
  }

  function getDatasource() {
    return $this->registry->get('table:blogentries');
  }

  function GET() {
    $datasource = $this->getDatasource();
    $resultset = Array();
    $results = $datasource->select('published', 'desc');
    foreach (array_slice($results, $this->state->get('offset'), 3) as $record) {
      $resultset[] = $record;
    }
    $model = Array(
      'resultset' => $resultset,
      'pagination' => $this->calculatePagination(count($results), 3),
    );
    return $this->render("../templates/blog/index.tpl.php", $model);
  }

  function calculatePagination($total, $step) {
    $steps = Array();
    for ($i=0,$idx=1; $i < $total; $i += $step, $idx++) {
      $steps[] = new ArrayObject(Array(
        'index' => $idx,
        'href' => $this->url('', Array('offset' => $i)),
      ), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST);
    }
    return $steps;
  }

  function HEAD() {
    throw new k_http_Response(200);
  }

  protected function createSubController($name) {
    if ($name == 'create') {
      return new Controller_Blog_Create($this, $name);
    }
    return new Controller_Blog_Show($this, $name);
  }

  protected function forward($name) {
    $response = parent::forward($name);
    $model = Array(
      'href' => $this->url(),
      'title' => "index",
      'content' => $response,
    );
    return $this->render("../templates/wrapper.tpl.php", $model);
  }
}
