<?php
class k_http_Redirect extends k_http_Response
{
  function __construct($url) {
    $content = '<html><head>'
      .'<meta http-equiv="Refresh" content="1;url='.$url.'">'
      .'</head><body>'
      .'<a href="'.$url.'">'.$url.'</a>'
      .'</body></html>';
    parent::__construct(303, $content);
    $this->setHeader("Location", $url);
  }
}
