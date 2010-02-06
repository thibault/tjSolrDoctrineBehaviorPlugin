<?php

/**
 * This is a bridge to the Apache_Solr_Service class
 **/
class Search_Handler_Solr implements Search_Handler_Interface
{
  protected $_service;

  /**
   * __construct builds a Apache_Solr_Service
   *
   * @param $host The Solr host
   * @param $port The Solr port
   * @param $path The Solr url prefix
   **/
  public function __construct($host, $port, $path)
  {
    $this->_service = new Apache_Solr_Service($host, $port, $path);
  }

  /**
   * @see Search_Handler_Interface::isAvailable()
   **/
  public function isAvailable()
  {
    return $this->_service->ping();
  }

  /**
   * Builds an Apache_Solr_Document to pass to Apache_Solr_Service
   **/
  public function buildDocument(array $document)
  {
    $doc = new Apache_Solr_Document();

    foreach($document as $fieldName => $field)
    {
      $value = $field['value'];

      // Apache_Solr_Document always expect arrays
      if(!is_array($value))
        $value = array($value);

      $fieldBoost = $field['boost'];

      $doc->setField($fieldName, $value, $fieldBoost);
    }

    return $doc;
  }

  /**
   * @see Search_Handler_Interface::index()
   **/
  public function index(array $document)
  {
    $doc = $this->buildDocument($document);
    $this->_service->addDocument($doc);
  }

  /**
   * @see Search_Handler_Interface::unindex()
   **/
  public function unindex($uniqueId)
  {
    $this->_service->deleteById($uniqueId);
  }

  /**
   * @see Search_Handler_Interface::deleteAllFromClass()
   **/
  public function deleteAllFromClass($class)
  {
    $q = "sf_meta_class:$class";
    $this->_service->deleteByQuery($q);
  }

  /**
   * @see Search_Handler_Interface::query()
   **/
  public function search($query, $offset = 0, $limit = 10, $params = array())
  {
    $result = $this->_service->search($query, $offset, $limit, $params);
    return json_decode($result->getRawResponse(), true);
  }

  /**
   * @see Search_Handler_Interface::commit()
   **/
  public function commit()
  {
    $this->_service->commit();
  }
}
