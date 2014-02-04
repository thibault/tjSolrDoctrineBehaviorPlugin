<?php

/**
 * Rebuild the index of solr.
 *
 * @package tjSolrDoctrineBehaviorPlugin
 * @subpackage task
 * @author Marc Weistroff <mweistroff@uneviemoinschere.com>
 * @author Thibault Jouannic <thibault@jouannic.fr>
 * @author Ashton Honnecke <ashton@pixelstub.com>
 */
class rebuildIndexTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    // add your own arguments here
    $this->addArguments(array(
      // We need a model class to have the connexion parameters
      new sfCommandArgument('model', sfCommandArgument::REQUIRED, 'The model name'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', null),
      new sfCommandOption('offset', null, sfCommandOption::PARAMETER_REQUIRED, 'The offset', 0),
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'The limit', 20)
    ));

    $this->namespace        = 'solr';
    $this->name             = 'rebuild-index';
    $this->briefDescription = 'rebuild the solr index';
    $this->detailedDescription = <<<EOF
The [rebuild-index|INFO] rebuilds the entire solr index.
Call it with:

  [php symfony solr:rebuild-index ModelClass|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

    $confirm = $this->askConfirmation(
      "\n This will rebuild everything in the Solr index. Are you sure you want to proceed? (y/N)\n",
      'QUESTION',
      false);
    if(!$confirm)
    {
      $this->logSection('solr', 'task aborted');
      return 1;
    }

    $model = $arguments['model'];
    $objects = Doctrine_Core::getTable($model)->createQuery('m')
        ->limit($options['limit'])
        ->offset($options['offset'])
        ->execute()
    ;

    foreach($objects as $object) {
        $object->deleteFromIndex();
        $object->addToIndex();
        $object->free();
    }

    $this->logSection('solr', 'Index has been rebuilt');
  }
}
