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

$t->comment('-> Search not existing content');
$results = Doctrine::getTable('Post')->search('gloubigoulba');
$t->is($results, array());

$t->comment('-> Indexing content');

$thread = new Thread();
$thread->title = 'test thread';

$post = new Post();
$post->title = 'gloubigoulba';
$post->body = 'this is my body';
$post->Thread = $thread;
$post->save();

$results = Doctrine::getTable('Post')->search('gloubigoulba');
$t->isnt($results, array());

