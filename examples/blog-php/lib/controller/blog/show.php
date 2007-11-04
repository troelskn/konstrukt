<?php
class Controller_Blog_Show extends k_Controller
{
  public $map = Array(
    'edit' => 'Controller_Blog_Edit',
    'delete' => 'Controller_Blog_Delete'
  );

  function getDatasource() {
    return $this->context->getDatasource();
  }

  function getModel() {
    return $this->getDatasource()->fetchByPK($this->name);
  }

  protected function forward($name) {
    $response = parent::forward($name);
    $model = Array(
      'href' => $this->url(),
      'title' => $this->getModel()->name,
      'content' => $response,
    );
    return $this->render("../templates/wrapper.tpl.php", $model);
  }

  function GET() {
    $record = $this->getModel();
    if (is_null($record)) {
      throw new k_http_Response(404);
    }
    return $this->render("../templates/blog/show.tpl.php");
  }

  function HEAD() {
    if (is_null($this->getModel())) {
      throw new k_http_Response(404);
    }
    throw new k_http_Response(200);
  }
}
