<?php
/**
 * Searchable via Solr template
 *
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  Template
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
class Doctrine_Template_Solr extends Doctrine_Template
{
  protected $_options = array(
    'host' => 'localhost',
    'port' => '8983',
    'path' => '/solr',
    'key' => 'sf_unique_id',
    'fields' => array(),
    'fieldmap' => array(),
    'boost' => array()
  );

  /**
   * @var Search_Service $_search This is the way to handle Solr communication
   **/
  private $_search;
  
  /**
   * @var string $_sf_meta_class A String representing the meta class of the model
   **/
  private $_sf_meta_class;

  public function setTableDefinition()
  {
    $this->addListener(new Doctrine_Template_Listener_Solr($this->_options));
  }

  public function setUp()
  {
    $searchHandler = new Search_Handler_Solr(
      $this->_options['host'],
      $this->_options['port'],
      $this->_options['path']
    );
    
    $this->_search = new Search_Service($searchHandler);
  }

  /**
   * Returns the meta class which will be set as the sf_meta_class solr field
   * 
   * Defaults to the model classname. When a meta_field option is set, this
   * value will be retrieved from the object field set in 'meta_field'
   **/
  private function getMetaClass() {
    
    if (isset($this->_sf_meta_class)) 
      return $this->sf_meta_class;
      
    if (isset($this->_options['meta_field']))
      $this->_sf_meta_class = $this->getInvoker()->get($this->_options['meta_field']);
    else $this->_sf_meta_class = get_class($this->getInvoker());
    
    return $this->_sf_meta_class;
    
  }

  /**
   * Override the search handler
   *
   * Only useful for tests, to replace the actual search handler by a mock
   **/
  public function setSearchHandlerTableProxy(Search_Handler_Interface $handler)
  {
    $this->_search = new Search_Service($handler);
  }

  /**
   * Return true if the search is currently available
   **/
  public function isSearchAvailableTableProxy()
  {
    return $this->_search->isAvailable();
  }

  /**
   * Get a unique document identifier
   **/
  public function getUniqueId()
  {
    return sprintf('%s_%s', $this->getMetaClass(), $this->getInvoker()->getId());
  }

  /**
   * Index the invoker into Solr
   **/
  public function addToIndex()
  {
    $this->_search->addToIndex($this->getInvoker());
  }

  /**
   * Delete the invoker from index
   **/
  public function deleteFromIndex()
  {
    $this->_search->deleteFromIndex($this->getInvoker());
  }

  /**
    * Build a Document for Solr indexing
    *
    * @return array
   **/
  public function getFieldsArray()
  {
    $document = array();
    $invoker = $this->getInvoker();

    // Set document key
    $document[$this->_options['key']]['value'] = $this->getUniqueId();

    // set meta data
    $document['sf_meta_class']['value'] = $this->getMetaClass();
    $document['sf_meta_id']['value'] = $invoker->getId();

    // Set others fields
    $fields = $this->_options['fields'];
    $map = $this->_options['fieldmap'];
    $boost = $this->_options['boost'];
    foreach($fields as $field)
    {
      $fieldName = array_key_exists($field, $map) ? $map[$field] : $field;
      $fieldBoost = array_key_exists($field, $boost) ? $boost[$field] : 1;

      $value = $invoker->get($field);

      $document[$fieldName]['value'] = $value;
      $document[$fieldName]['boost'] = $fieldBoost;
    }

    return $document;
  }

  /**
    * Remove every indexed documents from the invoker class
    *
    * handle with care
   **/
  public function deleteIndexTableProxy()
  {
    $this->_search->deleteIndex($this->getMetaClass());
  }

  /**
   * Performs a research through Solr
   *
   * @return array The solr response as a php array
   **/
  public function searchTableProxy($search, $offset = 0, $limit = 30, $params = array())
  {    
    return $this->_search->search($search, $offset, $limit, $this->getMetaClass(), $params);
  }

  /**
   * Generate a doctrine query based on a Solr search
   *
   * @return Doctrine_Query
   **/
  public function createSearchQueryTableProxy($search, $offset = 0, $limit = 30, $params = array())
  {
    $response = $this->getTable()->search($search, $offset, $limit, $params);

    $pks = array();
    foreach($response['response']['docs'] as $doc)
    {
      $pks[] = $doc['sf_meta_id'];
    }

    $q = $this->getTable()->createQuery();
    $alias = $q->getRootAlias();
    $q->select($alias.'.*');

    if($pks)
    {
      $q->whereIn($alias.'.id', $pks);
      // preserve score order
      $q->addSelect(sprintf('FIELD(%s.id,%s) as field', $alias, implode(',', $pks)));
      $q->orderBy('field');
    }
    else
    {
      $q->whereIn($alias.'.id', -1);
    }

    return $q;
  }
  

  /**
    * Starts a transaction for indexing
    *
    * When using a transaction, the amount of processing that solr does
    * decreases, increasing indexing performance. Without this, we
    * sends a commit after every document that is indexed. Transactions can be
    * nested, when commit() is called the same number of times as
    * beginTransaction(), we send a commit.
   **/
  public function beginTransactionTableProxy()
  {
    $this->_search->beginTransaction();
  }

  /**
    * Ends a transaction, and sends a commit message to Solr
    *
    * As transactions can be nested, we only send a real commit
    * when all transactions are closed
   **/
  public function commitTableProxy()
  {
    $this->_search->commit();
  }

  /**
   * Returns true if we're in a middle of a transaction, false otherwise
   **/
  public function inTransactionTableProxy()
  {
    return $this->_search->inTransaction();
  }
}
