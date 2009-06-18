<?php
/**
 * This class allows for outputting body despite of HTTP errors.
 * This makes it possible to create custom errorpages.
 */
class k_http_Response_Error extends k_http_Response
{
    function out()
    {
        if (session_id()) {
            session_write_close();
        }
        $this->sendStatus();
        if ($this->status >= 400) {
            // return;
        }
        $this->sendHeaders();
        $this->sendBody();
    }
}