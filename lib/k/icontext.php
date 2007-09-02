<?php
/**
  * The context forms the interface between [[controllers|#class-k_controller]]
  * and their surrounding scope.
  */
interface k_iContext
{
  /**
    * Creates a URL to this controller, with additional
    * parameters.
    *
    * @param    string    $href    URI-subspace
    * @param    array     $args    GET-parameters
    * @return string
    */
  function url($href = "", $args = Array());
  /**
    * Returns the URI-subspace for this controller.
    *
    * @return string
    */
  function getSubspace();
  /**
    * Returns the registry.
    *
    * @return k_Registry
    */
  function getRegistry();
  /**
    * Returns a new URL-state container
    *
    * @return k_UrlState
    */
  function getUrlStateContainer($namespace = "");
}