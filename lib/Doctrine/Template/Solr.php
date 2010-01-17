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

  private $_solr;
  private $_inTransaction;

  public function setTableDefinition()
  {
    $this->addListener(new Doctrine_Template_Listener_Solr($this->_options));
  }

  public function setUp()
  {
    $this->_inTransaction = 0;
    $this->_solr = new Apache_Solr_Service($this->_options['host'],
                                      $this->_options['port'],
                                      $this->_options['path']);
  }

  /**
    * Return true if the solr handler is available and connected
   **/
  public function isSearchAvailableTableProxy()
  {
    return $this->_solr->ping();
  }

  /**
   * Get a unique solr document identifier
   **/
  public function getSolrId()
  {
    return sprintf('%s_%s', get_class($this->getInvoker()), $this->getInvoker()->getId());
  }

  /**
   * Index the invoker into Solr
   **/
  public function addToIndex()
  {
    $invoker = $this->getInvoker();
    $solr = $this->_solr;

    $solr->addDocument($invoker->getSolrDocument());

    if($this->_inTransaction == 0)
      $solr->commit();
  }

  /**
   * Delete the invoker from index
   **/
  public function deleteFromIndex()
  {
    $invoker = $this->getInvoker();
    $solr = $this->_solr;

    $solr->deleteById($invoker->getSolrId());

    if($this->_inTransaction == 0)
      $solr->commit();
  }

  /**
    * Build a Document for Solr indexing
    *
    * @return Apache_Solr_Document
   **/
  public function getSolrDocument()
  {
    $document = new Apache_Solr_Document();
    $invoker = $this->getInvoker();

    // Set document key
    $document->addField($this->_options['key'], $this->getSolrId());

    // set meta data
    $document->addField('sf_meta_class', get_class($invoker));
    $document->addField('sf_meta_id', $invoker->getId());

    // Set others fields
    $fields = $this->_options['fields'];
    $map = $this->_options['fieldmap'];
    $boost = $this->_options['boost'];
    foreach($fields as $field)
    {
      $fieldName = array_key_exists($field, $map) ? $map[$field] : $field;
      $fieldBoost = array_key_exists($field, $boost) ? $boost[$field] : 1;

      $value = $invoker->get($field);

      // Solr_Apache_Document always expect an array
      if(!is_array($value))
        $value = array($value);

      $document->setField($fieldName, $value, $fieldBoost);
    }

    return $document;
  }

  /**
    * Remove every indexed documents
    *
    * handle with care
   **/
  public function deleteIndexTableProxy()
  {
    $solr = $this->_solr;
    $q = 'sf_meta_class:'.get_class($this->getInvoker());
    $solr->deleteByQuery($q);

    if($this->_inTransaction == 0)
      $solr->commit();
  }

  /**
   * Performs a research through Solr
   *
   * @return array The solr response as a php array
   **/
  public function searchTableProxy($search, $offset = 0, $limit = 30)
  {
    $solr = $this->_solr;

    // We filter the results types
    $params = array(
      'fq' => 'sf_meta_class:'.get_class($this->getInvoker())
    );

    $results = $solr->search($search, $offset, $limit, $params);
    $response = json_decode($results->getRawResponse());

    return $response->response;
  }

  /**
   * Generate a doctrine query based on a Solr search
   *
   * @return Doctrine_Query
   **/
  public function createSearchQueryTableProxy($search, $limit = 256)
  {
    $response = $this->getTable()->search($search, 0, $limit);

    $pks = array();
    foreach($response->docs as $doc)
    {
      $pks[] = $doc->sf_meta_id;
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
    $this->_inTransaction++;
  }

  /**
    * Ends a transaction, and sends a commit message to Solr
    *
    * As transactions can be nested, we only send a real commit
    * when all transactions are closed
   **/
  public function commitTableProxy()
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
   * Returns true if we're in a middle of a transaction, false otherwise
   **/
  public function inTransactionTableProxy()
  {
    return $this->_inTransaction > 0;
  }
}
