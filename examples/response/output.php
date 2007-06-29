<?php
require_once('../../std.inc.php');

    $data = "binary\0safe?";
    $response = new k_http_Response(200, $data);
    $response->encoding = NULL;
    $response->contentType = "text/text";
    $response->out();
