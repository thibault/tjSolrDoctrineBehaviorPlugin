<?php
/**
 * Searchable via Solr template
 **/
class Doctrine_Template_Solr extends Doctrine_Template
{
  protected $_options = array(
    'host' => 'localhost',
    'port' => '8983',
    'path' => '/solr',
    'key' => 'id'
  );

  public function setTableDefinition()
  {
    $this->addListener(new SolrTemplateListener($this->_options));
  }

  public function setUp()
  {
  }
}
