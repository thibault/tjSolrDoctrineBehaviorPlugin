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

  public function preDelete(Doctrine_Event $event)
  {
    $invoker = $event->getInvoker();

    $invoker->deleteFromIndex();
  }

  /**
   * Index invoker fields into Solr
   **/
  protected function updateIndex($event)
  {
    $invoker = $event->getInvoker();

    // Delete the doc from index if it already exists
    if(!$invoker->isNew())
      $invoker->deleteFromIndex();

    $invoker->addToIndex();
  }
}
