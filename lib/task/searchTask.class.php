<?php

/**
 * Performs a research in the Solr index
 *
 * @package tjSolrDoctrineBehaviorPlugin
 * @subpackage task
 * @author Thibault Jouannic <thibault@jouannic.fr>
 */
class searchTask extends sfBaseTask
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
      new sfCommandArgument('query', sfCommandArgument::REQUIRED, 'The query string'),
    ));

    $this->addOptions(array(
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', null),
      new sfCommandOption('offset', null, sfCommandOption::PARAMETER_REQUIRED, 'The search offset', 0),
      new sfCommandOption('limit', null, sfCommandOption::PARAMETER_REQUIRED, 'The search limit', 10),
      // add your own options here
    ));

    $this->namespace        = 'solr';
    $this->name             = 'search';
    $this->briefDescription = 'search in solr';
    $this->detailedDescription = <<<EOF
The [search|INFO] task executes a Solr search and displays the result
Call it with:

  [php symfony solr:search ModelClass query|INFO]

You can also add additional parameters:

  [php symfony solr:search ModelClass "query" --offset=0 --limit=10|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $databaseManager = new sfDatabaseManager($this->configuration);

    $model = $arguments['model'];
    $query = $arguments['query'];
    $offset = $options['offset'];
    $limit = $options['limit'];

    if(!Doctrine_Core::getTable($model)->isSearchAvailable())
    {
      throw new RuntimeException('Search is unavailable. Make sure Solr is started');
    }

    $this->logSection('solr', 'Running search');

    $q = Doctrine_Core::getTable($model)->createSearchQuery($query, $offset, $limit);
    $results = $q->fetchArray();

    $this->log(array(
      sprintf('found %s result(s)', count($results)),
      sfYaml::dump($results, 4)
    ));
  }
}
