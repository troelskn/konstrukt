<?php
class Controller_Blog_Create extends k_Controller
{
  function __construct(k_iContext $parent, $name = "") {
    parent::__construct($parent, $name);
    $datasource = $this->context->getDatasource();
    $descriptors = Array();
    foreach ($datasource->reflect() as $column => $meta) {
      if ($column == "name") {
        $descriptors[] = Array("name" => $column, "filters" => Array('trim', 'strtolower'));
      } else {
        $descriptors[] = Array("name" => $column);
      }
    }
    $this->form = new k_FormBehaviour($this, "../templates/blog/form.tpl.php");
    $this->form->descriptors = $descriptors;
  }

  function execute() {
    return $this->form->execute();
  }

  function validate($values) {
    return TRUE;
  }

  function validHandler($values) {
    $gateway = $this->context->getDatasource();
    if (!$gateway->insert($values)) {
      throw new Exception("insert failed");
    }
    // It would be proper REST to reply with 201, but browsers doesn't understand that
    throw new k_http_Redirect($this->context->url($values['name']));
  }
}