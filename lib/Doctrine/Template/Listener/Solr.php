<?php

/**
 * Update Solr index when object is created / updated / deleted
 *
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  Listener
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
class Doctrine_Template_Listener_Solr extends Doctrine_Record_Listener
{
  protected $_options;

  public function __construct(array $options)
  {
    $this->_options = $options;
  }

  public function postInsert(Doctrine_Event $event)
  {
    $this->updateIndex($event);
  }

  public function postUpdate(Doctrine_Event $event)
  {
    $this->updateIndex($event);
  }

  /**
   * Index invoker fields into Solr
   **/
  protected function updateIndex($event)
  {
    $invoker = $event->getInvoker();
    $columns = $invoker->getTable()->getColumns();

    foreach($columns as $column => $attributes)
    {
      if(in_array($column, $this->_options['fields']))
      {
        $columnType = $attributes['type'];
      }
    }
  }
}
