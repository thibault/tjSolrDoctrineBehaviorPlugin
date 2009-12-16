<?php

if (!isset($_SERVER['SYMFONY']))
{
  throw new RuntimeException('Could not find symfony core libraries.');
}

require_once $_SERVER['SYMFONY'].'/autoload/sfCoreAutoload.class.php';
sfCoreAutoload::register();

class ProjectConfiguration extends sfProjectConfiguration
{
  public function setup()
  {
    $this->enablePlugins('sfDoctrinePlugin');
    $this->enablePlugins('tjSolrDoctrineBehaviorPlugin');
    $this->setPluginPath('sfDoctrinePlugin', $_SERVER['SYMFONY'].'/plugins/sfDoctrinePlugin');
    $this->setPluginPath('tjSolrDoctrineBehaviorPlugin', dirname(__FILE__).'/../../../..');
  }
}
