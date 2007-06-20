<?php
class Root extends k_Dispatcher
{
  public $debug = TRUE;
  function GET() {
    return "<h1>Root</h1><p>This page is intentionally left blank</p>";
  }

  function HEAD() {
    throw new k_http_Response(200);
  }
}
