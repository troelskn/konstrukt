<?php

  /*
notes on charsets
-----------------

two problems, regarding charsets:
  #1 what encoding is the internal string in?
    -> technically, the correct thing to do, is to make utf-8 default, but this conflicts with PHP defaulting to latin1
    -> alternatively, latin1 could be default, allowing the user to specify that data is utf-8
  #2 what encoding should the output be in (Eg. what is the charset-part of the Content-Type header)?
    -> if specified, then use that
    -> else use a default, based on content-type

- in-memory strings
- external i/o (http-streams):
  - input (form-submit, query-string, uri)
  - output:
    - encoding
    - header (Content-Type)
- internal i/o:
  - files (php-sources, templates)
  - database-connection
  - database-storage
  - and more ...

*/

  /**
   * A responsecharset governs how the response is sent back to the client.
   * the httpresponse holds the content as utf-8, so each responsecharset should be able to convert
   * from this base
   */
interface k_charset_ResponseCharset {
  function name();
  function encode($utf8_string);
}

class k_charset_Utf8 implements k_charset_ResponseCharset {
  /**
    * @return string
    */
  function name() {
    return 'utf-8';
  }
  /**
    * @param string
    * @return string
    */
  function encode($utf8_string) {
    return $utf8_string;
  }
}

class k_charset_Latin1 implements k_charset_ResponseCharset {
  function name() {
    return 'iso-8859-1';
  }
  function encode($utf8_string) {
    return utf8_decode($utf8_string);
  }
}

/**
 * A strategy for encoding/decoding charsets
 */
interface k_charset_CharsetStrategy {
  function decodeInput($input);
  function responseCharset();
  function isInternalUtf8();
}

/**
 * Full UTF-8
 * This is the preferred configuration
 * If you're using PHP < 6
 * You should use the [mb-string extension](http://docs.php.net/manual/en/mbstring.overload.php) to replace the built-ins. This is not crucial, but a good idea none the less.
 * You also need to make sure that all files (php and templates) are utf-8 encoded
 * If you use a database, make sure that the transfer charset is utf-8 (on mysql use: SET NAMES = 'utf8')
 */
class k_charset_Utf8CharsetStrategy implements k_charset_CharsetStrategy {
  function decodeInput($input) {
    return $input;
  }
  /**
    * @return k_charset_Utf8
    */
  function responseCharset() {
    return new k_charset_Utf8();
  }
  /**
    * @return boolean
    */
  function isInternalUtf8() {
    return true;
  }
}

/**
 * Full latin1, throughout the application
 * While this is the native solution for PHP < 6, it isn't recommended for new applications. Use FauxUtf8 or preferably Utf8.
 */
class k_charset_Latin1CharsetStrategy implements k_charset_CharsetStrategy {
  /**
    * @param array
    * @return array
    */
  function decodeInput($input) {
    return $input;
  }
  function responseCharset() {
    return new k_charset_Latin1();
  }
  function isInternalUtf8() {
    return false;
  }
}

/**
 * All input/output as UTF-8, but latin1 on the inside
 * This was the default charset handling of Konstrukt v. 1 - It is now deprecated in favour of full UTF-8
 * It offers few benefits over a simple latin1 strategy, but forms a lowest dommon denominator when using Ajax (javascript defaults to UTF-8)
 * It expects everything within PHP (files and strings) to be latin1. This includes any data received from the database.
 * It will encode/decode the outside as UTF-8, so the page appears in UTF-8 for the client, even if it is limited to the latin1 subset.
 */
class k_charset_FauxUtf8CharsetStrategy implements k_charset_CharsetStrategy {
  function decodeInput($input) {
    if (is_array($input)) {
      $output = array();
      foreach ($input as $key => $value) {
        $output[$key] = $this->decodeInput($value);
      }
      return $output;
    }
    return utf8_decode($input);
  }
  function responseCharset() {
    return new k_charset_Utf8();
  }
  function isInternalUtf8() {
    return false;
  }
}
