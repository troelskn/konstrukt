<?php
class k_http_Authenticate extends k_http_Response
{
  public $realm;

  function __construct($realm = "basic") {
    parent::__construct(401);
    $this->realm = $realm;
  }

  function out() {
    header("WWW-Authenticate: Basic realm=\"".$this->realm."\"");
    return parent::out();
  }
}
