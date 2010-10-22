<?php

/**
 * Performs the common search operations
 *
 * Actual communication with the search engine is delegated to a Search_Handler
 *
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  Search
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
class Search_Service
{
  private $_searchHandler;
  private $_inTransaction;

  /**
   * __construct
   *
   * @param Search_Handler_Interface $searchHandler The object that actually communicate to the search engine
   * $return void
   **/
  public function __construct(Search_Handler_Interface $searchHandler)
  {
    $this->_inTransaction = 0;
    $this->_searchHandler = $searchHandler;
  }

  /**
   * Let us know if the search engine is currently available
   *
   * @return boolean
   **/
  public function isAvailable()
  {
    return $this->_searchHandler->isAvailable();
  }

  /**
   * Send a Doctrine record to the search engine for indexing
   *
   * @param sfDoctrineRecord $record The record to index
   **/
  public function addToIndex(sfDoctrineRecord $record)
  {
    $this->_searchHandler->index($record->getFieldsArray());

    if($this->_inTransaction == 0)
      $this->_searchHandler->commit();
  }

  /**
   * Request a document deletion from the search engine
   *
   * @param sfDoctrineRecord $record The record to unindex
   **/
  public function deleteFromIndex(sfDoctrineRecord $record)
  {
    $this->_searchHandler->unindex($record->getUniqueId());

    if($this->_inTransaction == 0)
      $this->_searchHandler->commit();
  }

  /**
   * Request a complete index deletion (only for a class)
   *
   * @param $class The class of objects that should be removed
   **/
  public function deleteIndex($class)
  {
    $this->_searchHandler->deleteAllFromClass($class);

    if($this->_inTransaction == 0)
      $this->_searchHandler->commit();
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
  public function search($search, $offset, $limit, $class, $params)
  {
    // We filter the results types
    if(isset($params['fq'])) {
      if (is_array($params['fq'])) {
        $params['fq'][] = "sf_meta_class:$class";
      } else $params['fq'] = array("sf_meta_class:$class", $params['fq']);
    } else
      $params['fq'] = "sf_meta_class:$class";

    return $this->_searchHandler->search($search, $offset, $limit, $params);
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
      $this->_searchHandler->commit();
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
