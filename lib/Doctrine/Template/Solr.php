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
    'boost' => array(),
    'realtime' => true,
  );

  /**
   * @var Search_Service $_search This is the way to handle Solr communication
   **/
  private $_search;

  public function setTableDefinition()
  {
    // Don't setup listener if realtime option is false
    if ($this->_options['realtime'])
    {
        $this->addListener(new Doctrine_Template_Listener_Solr($this->_options));
    }
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
    return sprintf('%s_%s', get_class($this->getInvoker()), $this->getInvoker()->getPrimaryKey());
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
    $document['sf_meta_class']['value'] = get_class($invoker);
    $document['sf_meta_id']['value'] = $invoker->getPrimaryKey();

    // Should we perform specific i18n operations?
    $isI18N = $invoker->getTable()->hasTemplate('Doctrine_Template_I18n');
    if ($isI18N)
    {
      // Make sure we retrieve lang codes
      $langs = array();
      foreach($invoker->Translation->getKeys() as $key)
      {
        if (is_string($key))
        {
          $langs[] = $key;
        }
      }
      $translatedFields = $invoker->Translation->getFirst()->getTable()->getFieldNames();
    }

    // Set others fields
    $fields = $this->_options['fields'];
    $map = $this->_options['fieldmap'];
    $boost = $this->_options['boost'];
    foreach($fields as $field)
    {
      $fieldBoost = array_key_exists($field, $boost) ? $boost[$field] : 1;

      // If the current field is part of the i18n table
      if ($isI18N && in_array($field, $translatedFields))
      {
        foreach ($langs as $lang)
        {
          $fieldName = $field . '_' . $lang;
          $value = $invoker->Translation[$lang]->get($field);
          $document[$fieldName]['value'] = $value;
          $document[$fieldName]['boost'] = $fieldBoost;
        }
      }
      else
      {
        $fieldName = array_key_exists($field, $map) ? $map[$field] : $field;
        $value = $invoker->get($field);

        $document[$fieldName]['value'] = $value;
        $document[$fieldName]['boost'] = $fieldBoost;
      }
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
    $this->_search->deleteIndex(get_class($this->getInvoker()));
  }

  /**
   * Performs a research through Solr
   *
   * @return array The solr response as a php array
   **/
  public function searchTableProxy($search, $offset = 0, $limit = 30, $params = array())
  {
    return $this->_search->search($search, $offset, $limit, get_class($this->getInvoker()), $params);
  }

  /**
   * Generate a doctrine query based on a Solr search
   *
   * @return Doctrine_Query
   **/
  public function createSearchQueryTableProxy($search, $offset = 0, $limit = 30, array $params = array())
  {
    $primaryKey =  $this->getTable()->getIdentifier();
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
      $q->whereIn($alias.'.'.$primaryKey, $pks);
      // preserve score order
      $q->addSelect(sprintf('FIELD(%s.%s,%s) as field', $alias, $primaryKey, implode(',', $pks)));
      $q->orderBy('field');
    }
    else
    {
      $q->whereIn($alias.'.'.$primaryKey, -1);
    }
    
    var_dump($response)."\n";
    echo $q->getSqlQuery()."\n";

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
