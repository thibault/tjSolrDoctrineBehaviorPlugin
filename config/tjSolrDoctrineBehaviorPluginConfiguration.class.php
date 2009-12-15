<?php

/**
 * @package     tjSolrDoctrineBehaviorPlugin
 * @subpackage  config
 * @author      Thibault Jouannic <thibault@jouannic.fr>
 **/
class tjSolrDoctrineBehaviorPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    if($this->configuration instanceOf sfApplicationConfiguration)
    {
      $configCache = $this->configuration->getConfigCache();
      $configCache->registerConfigHandler('config/solr.yml', 'sfDefineEnvironmentConfigHandler', array('prefix' => 'solr_'));
      require_once($configCache->checkConfig('config/solr.yml'));
    }
  }
}
