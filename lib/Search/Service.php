<?php

/**
 * Performs the common search operations
 *
 * Communication with the search engine is delegated to a Search_Handler
 *
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  Search
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
class Search_Service
{
  private $_solr;
  private $_inTransaction;

  /**
   * __construct
   *
   * @param array options
   * $return void
   **/
  public function __construct(array $options = array())
  {
    $this->_inTransaction = 0;
    $this->_solr = new Apache_Solr_Service(
      $options['host'],
      $options['port'],
      $options['path']
    );
  }

  /**
   * Let us know if the search engine is currently available
   *
   * @return boolean
   **/
  public function isAvailable()
  {
    return true;
  }

  /**
   * Send a Doctrine record to the search engine for indexing
   *
   * @param sfDoctrineRecord $record The record to index
   **/
  public function addToIndex(sfDoctrineRecord $record)
  {
    $this->_solr->addDocument($record->getSolrDocument());

    if($this->_inTransaction == 0)
      $this->_solr->commit();
  }

  /**
   * Request a document deletion from the search engine
   *
   * @param sfDoctrineRecord $record The record to unindex
   **/
  public function deleteFromIndex(sfDoctrineRecord $record)
  {
    $this->_solr->deleteById($record->getUniqueId());

    if($this->_inTransaction == 0)
      $this->_solr->commit();
  }

  /**
   * Request a complete index deletion (only for a class)
   *
   * @param $class The class of objects that should be removed
   **/
  public function deleteIndex($class)
  {
    $q = "sf_meta_class:$class";
    $this->_solr->deleteByQuery($q);

    if($this->_inTransaction == 0)
      $this->_solr->commit();
  }

  /**
   * Performs a search query through the search engine
   *
   * @param string $search The actual search query
   * @param integer $offset
   * @param integer $limit
   * @param string $class Limit the type of object that should be returned
   * @return array The response
   **/
  public function search($search, $offset, $limit, $class)
  {
    // We filter the results types
    $params = array(
      'fq' => "sf_meta_class:$class"
    );

    $results = $this->_solr->search($search, $offset, $limit, $params);
    $response = json_decode($results->getRawResponse());

    return $response->response;
  }

  /**
   * Begins a transaction.
   *
   * Transactions can be nested
   **/
  public function beginTransaction()
  {
    $this->_inTransaction++;
  }

  /**
   * Ends a transaction. Sends a commit message to the search engine
   **/
  public function commit()
  {
    if($this->_inTransaction < 1)
      throw new sfException('Cannot commit when not in transaction');

    $this->_inTransaction--;

    if($this->_inTransaction == 0)
    {
      $this->_solr->commit();
    }
  }

  /**
   * Let us know if we're in the middle of a transaction
   *
   * @return boolean
   **/
  public function inTransaction()
  {
    return $this->_inTransaction > 0;
  }
}
