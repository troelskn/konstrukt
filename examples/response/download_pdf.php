<?php
require_once('../../examples/std.inc.php');

    $data = "foobar";
    $response = new k_http_Response(200, $data);
    $response->encoding = NULL;
    $response->contentType = "application/pdf";

    $response->setHeader("Content-Length", strlen($data));
    $response->setHeader("Content-Disposition", "attachment; filename=\"foobar.pdf\"");
    $response->setHeader("Content-Transfer-Encoding", "binary");
    $response->setHeader("Cache-Control", "Public");
    $response->setHeader("Pragma", "public");

    $response->out();
