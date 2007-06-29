<?php

    $data = "foobar";

    header("HTTP/1.1 200");
    header("Content-Type: "."application/pdf");
    header("Content-Length: ".strlen($data));
    header("Content-Disposition: "."attachment; filename=\"foobar.pdf\"");
    header("Content-Transfer-Encoding:"."binary");
    header("Cache-Control: "."Public");
    header("Pragma: "."public");

    echo $data;
