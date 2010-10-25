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

    try {
      $invoker = $event->getInvoker();      
      $invoker->deleteFromIndex();
    } catch (Exception $e) {
      $this->notifyException($e, $invoker);
    }

  }

  /**
   * Index invoker fields into Solr
   **/
  protected function updateIndex($event)
  {    
    try {
      $invoker = $event->getInvoker();

      // Delete the doc from index if it already exists
      // @todo always executed are we are in postInsert.
      if(!$invoker->isNew())
        $invoker->deleteFromIndex();

      $invoker->addToIndex();
      
    } catch (Exception $e) {
      $this->notifyException($e, $invoker);
    }
  }
  
  private function notifyException(Exception $e, sfDoctrineRecord $record) 
  {  
    
    $event = sfContext::getInstance()->getEventDispatcher()->notifyUntil(new sfEvent($record, 'solr.indexing_error', array('exception' => $e)));
    
    if ($event->isProcessed())
    {
      return;
    }
    
    throw $e;

  }
}