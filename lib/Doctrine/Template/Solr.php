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
    'key' => 'id',
    'fields' => array()
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

    // Set others fields
    $fields = $this->_options['fields'];
    foreach($fields as $field => $data)
    {
      if(is_array($data))
      {
        $solrName = $data['name'] ? $data['name'] : $field;
        $boost = $data['boost'] ? $data['boost'] : 1;
      }
      else
      {
        $solrName = $data;
        $boost = 1;
      }

      $value = $invoker->get($field);
      if(!is_array($value))
        $value = array($value);

      foreach($value as $fieldValue)
        $document->setField($fieldName, $fieldValue, $boost);
    }

    return $document;
  }

  /**
   * Performs a research through Solr
   **/
  public function searchTableProxy($search)
  {
  }
}
