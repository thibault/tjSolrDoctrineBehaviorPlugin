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

  protected static $solr;

  public function setTableDefinition()
  {
    $this->addListener(new Doctrine_Template_Listener_Solr($this->_options));
  }

  public function setUp()
  {
  }

  /**
    * Returns a solr connexion handler
    *
    * @return Apache_Solr_Service
   **/
  public function getSolrService()
  {
    static $solr;

    if(null === $solr)
    {
      $solr = new Apache_Solr_Service($this->_options['host'],
                                      $this->_options['port'],
                                      $this->_options['path']
      );
    }

    return $solr;
  }

  /**
    * Return true if the solr handler is available and connected
   **/
  public function isSearchAvailableTableProxy()
  {
    return $this->getSolrService()->ping();
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
    $solr = $invoker->getSolrService();

    $solr->addDocument($invoker->getSolrDocument());
    $solr->commit();
  }

  /**
   * Delete the invoker from index
   **/
  public function deleteFromIndex()
  {
    $invoker = $this->getInvoker();
    $solr = $invoker->getSolrService();

    $solr->deleteById($invoker->getSolrId());
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
      $fieldName = $map[$field] ? $map[$field] : $field;
      $boost = $boost[$field] ? $boost[$field] : 1;

      $value = $invoker->get($field);

      // Solr_Apache_Document always expect an array
      if(!is_array($value))
        $value = array($value);

      $document->setField($fieldName, $value, $boost);
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
    $solr = $this->getSolrService();
    $solr->deleteByQuery('*:*');
    $solr->commit();
  }

  /**
   * Performs a research through Solr
   *
   * @return array The solr response as a php array
   **/
  public function searchTableProxy($search)
  {
    $solr = $this->getSolrService();
    $results = $solr->search($search);
    $response = json_decode($results->getRawResponse());

    return $response->response;
  }
}
