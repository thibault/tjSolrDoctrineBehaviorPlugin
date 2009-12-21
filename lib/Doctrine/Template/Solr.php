<?php
/**
 * Searchable via Solr template
 * We're using some classes from the Search components, from the eZ Components
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
    * @return ezcSearchSolrHandler
   **/
  public function getSolrService()
  {
    static $solr;

    if(null === $solr)
    {
      try
      {
        $solr = new ezcSearchSolrHandler($this->_options['host'],
                                        $this->_options['port'],
                                        $this->_options['path']
        );
      }
      catch(Exception $e)
      {
        sfContext::getInstance()->getLogger()->warning('{tjSolrDoctrineBehaviorPlugin} ' . $e->getMessage());
      }
    }

    return $solr;
  }

  /**
    * Return true if the solr handler is available and connected
   **/
  public function isSearchAvailableTableProxy()
  {
    return $this->getSolrService() !== null;
  }

  /**
    * Computes a default document definition
    *
    * @return ezcSearchDocumentDefinition
   **/
  public function getDocumentDefinition()
  {
    $def = new ezcSearchDocumentDefinition(get_class($this->getInvoker()));
    $def->idProperty = 'id';
    $def->fields['id'] = new ezcSearchDefinitionDocumentField('id', ezcSearchDocumentDefinition::TEXT);
    $def->fields['title'] = new ezcSearchDefinitionDocumentField( 'title', ezcSearchDocumentDefinition::TEXT, 2, true, false, true);
    $def->fields['body'] = new ezcSearchDefinitionDocumentField( 'body', ezcSearchDocumentDefinition::TEXT, 1, false, false, false);

    return $def;
  }

  /**
   * Performs a research through Solr
   **/
  public function searchTableProxy($search)
  {
    $solr = $this->getSolrService();
    $def = $this->getDocumentDefinition();

    // We add the ezcsearch_type field to the definition automatically here, but we delete it as well
    // see ezcSearchSession
    $def->fields['ezcsearch_type'] = new ezcSearchDefinitionDocumentField('ezcsearch_type', ezcSearchDocumentDefinition::STRING);
    $query = $solr->createFindQuery(get_class($this->getInvoker()), $def);
    unset($def->fields['ezcsearch_type']);

    // Use eZ Components to parse a query string
    $qb = new ezcSearchQueryBuilder();
    $qb->parseSearchQuery($query, $search, array_keys($def->fields));

    try
    {
      $queryWord = join( ' AND ', $query->whereClauses );
      $resultFieldList = $query->resultFields;
      $highlightFieldList = $query->highlightFields;
      $facetFieldList = $query->facets;
      $limit = $query->limit;
      $offset = $query->offset;
      $order = $query->orderByClauses;
      $results = $solr->search( $queryWord, '', array(), $resultFieldList, $highlightFieldList, $facetFieldList, $limit, $offset, $order );
    }
    catch(Exception $e)
    {
      sfContext::getInstance()->getLogger()->warning('{tjSolrDoctrineBehaviorPlugin} ' . $e->getMessage());
    }

    var_dump($results);
    return $results;
  }
}
