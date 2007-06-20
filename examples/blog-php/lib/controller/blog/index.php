<?php
class Controller_Blog_Index extends k_Controller
{
  protected $offset = 0;

  function getDatasource() {
    return $this->registry->get('table:blogentries');
  }

  function execute() {
    $this->offset = (int) isset($this->GET['offset']) ? $this->GET['offset'] : 0;
    return parent::execute();
  }

  function GET() {
    $datasource = $this->getDatasource();
    $resultset = Array();
    $results = $datasource->select('published', 'desc');
    foreach (array_slice($results, $this->offset, 3) as $record) {
      $record['href'] = $this->url($record['name']);
      $resultset[] = $record;
    }
    $model = Array(
      'resultset' => $resultset,
      'create' => $this->url('create'),
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

  protected function forward($name) {
    if ($name == 'create') {
      $next = new Controller_Blog_Create($this, $name);
    } else {
      $next = new Controller_Blog_Show($this, $name);
    }

    $response = $next->handleRequest();
    $model = Array(
      'href' => $this->url(),
      'title' => "index",
      'content' => $response,
    );
    return $this->render("../templates/wrapper.tpl.php", $model);
  }
}
