<?php

/**
 * Doctrine_Template_Solr tests.
 */
include dirname(__FILE__).'/../../../bootstrap/bootstrap.php';

$t = new lime_test();

$t->comment('-> test template availability');
$t->ok(is_callable(array(Doctrine::getTable('Post'), 'isSearchAvailable')));

// Ensure Solr is started, or will automaticaly fail
$t->comment('-> test if Solr is reachable (will fail if not started)');
$t->ok(Doctrine::getTable('Post')->isSearchAvailable());
