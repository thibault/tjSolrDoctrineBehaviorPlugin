<?php

/**
 * Search_Handler_Interface
 *
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  Search
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
Interface Search_Handler_Interface
{
  /**
   * Test if search engine is available
   *
   * @return boolean
   **/
  public function isAvailable();

  /**
   * Add a document to the search engine index
   *
   * @param array $document The associative array describing the document to index
   **/
  public function index(array $document);

  /**
   * Deletes a document from the index
   *
   * @param string $uniqueId The document identifier
   **/
  public function unindex($uniqueId);

  /**
   * Deletets all elements from the specified class from the index
   *
   * @param string $class
   **/
  public function deleteAllFromClass($class);

  /**
   * Performs a query
   *
   * @param string $query
   * @param integer $offset
   * @param integer $limit
   * @param array $params Additional parameters
   **/
  public function search($query, $offset = 0, $limit = 10, $params = array());

  /**
   * Send a commit message to the search engine
   **/
  public function commit();
}
