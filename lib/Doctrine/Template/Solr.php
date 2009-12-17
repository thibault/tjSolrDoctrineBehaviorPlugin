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

  protected function getSolrService()
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

  public function isSearchAvailableTableProxy()
  {
    return $this->getSolrService()->ping();
  }
}
