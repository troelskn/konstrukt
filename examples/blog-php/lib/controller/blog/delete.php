<?php
class Controller_Blog_Delete extends k_Controller
{
  function __construct(k_iContext $parent, $name = "") {
    parent::__construct($parent, $name);
    $this->form = new k_FormBehaviour($this, "../templates/blog/delete.tpl.php");
  }

  function execute() {
    return $this->form->execute();
  }

  function validate($values) {
    return TRUE;
  }

  function validHandler($data) {
    $gateway = $this->context->getDatasource();
    $gateway->delete($this->context->getModel());
    throw new k_http_Redirect($this->context->context->url());
  }
}
