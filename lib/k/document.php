<?php
/**
  * The document is a container for properties of the HTML-document.
  *
  * The [[dispatcher|#class-k_dispatcher]] will render the document,
  * using the [[template|#class-k_document-property-template]] as
  * the template file, if supplied. Set it to NULL, if you do not wish to have
  * the dispatcher render the document.
  */
class k_Document
{
  /**
    * The template file to use for rendering the document.
    *
    * If set to NULL, the document won't be rendered by the dispatcher.
    * @var string
    */
  public $template = NULL;
  /**
    * The content-encoding.
    *
    * This should match the encoding of the response generated. It is highly
    * recommended to leave it as UTF-8
    * @var string
    */
  public $encoding = "UTF-8";
  /**
    * The title-tag of the document.
    *
    * @var string
    */
  public $title = "No Title";
  /**
    * External javascript files to load in the header section of the document.
    *
    * @var array
    */
  public $scripts = Array();
  /**
    * External style sheet files to load in the header section of the document.
    *
    * @var array
    */
  public $styles = Array();
  /**
    * Javascript code to execute at document load time.
    *
    * @var array
    */
  public $onload = Array();
}
