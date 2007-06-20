<?php
class Controller_Blog_Edit extends k_Controller
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

  function getDefaultValues() {
    return $this->context->getModel();
  }

  function execute() {
    $this->form->execute();
    return $this->form->execute();
  }

  function validate($values) {
    return TRUE;
  }

  function validHandler($values) {
    $gateway = $this->context->getDatasource();
    if (!$gateway->update($values, Array($gateway->pkey => $this->context->name))) {
      throw new Exception("update failed");
    }
    throw new k_http_Redirect($this->url("../.."));
  }
}