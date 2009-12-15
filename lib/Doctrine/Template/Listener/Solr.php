<?php

class Doctrine_Template_Listener_Solr extends Doctrine_Record_Listener
{
  protected $_options;

  public function __construct(array $options)
  {
    $this->_options = $options;
  }

  public function postInsert(Doctrine_Event $event)
  {
    $this->performIndex($event);
  }

  public function postUpdate(Doctrine_Event $event)
  {
    $this->performIndex($event);
  }

  /**
   * Index invoker fields into Solr
   **/
  protected function performIndex($event)
  {
    $invoker = $event->getInvoker();
    $columns = $invoker->getTable()->getColumns();

    foreach($columns as $column => $attributes)
    {
      if(!in_array($column, $this->_options['exclude']))
      {
        $columnType = $attributes['type'];
        var_dump($column);
        var_dump($columnType);
      }
    }
  }
}
