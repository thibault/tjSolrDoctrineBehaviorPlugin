<?php

/**
 * Doctrine_Template_Listener_Solr tests.
 */
include dirname(__FILE__).'/../../../../bootstrap/bootstrap.php';

$t = new lime_test(4);

// We need access to Solr to run our tests. Ensure it is running
if(!Doctrine::getTable('Post')->isSearchAvailable())
  die();

// Ensure we're working on a clean index
Doctrine::getTable('Post')->deleteIndex();
Doctrine::loadData(dirname(__FILE__).'/../../../fixtures/project/data/fixtures');

// Check if index is updated when the object is
$t->comment('-> postInsert');
$post = new Post();
$post->title = 'foobarbaz ';
$post->body = 'this is my body';
$post->Thread = new Thread();
$post->Thread->title = 'test thread';
$post->save();

$otherPost = new Post();
$otherPost->title = 'foobar';
$otherPost->body = 'This is a gloubigoulba body';
$otherPost->Thread = $post->Thread;
$otherPost->save();

$results = Doctrine::getTable('Post')->search('foobarbaz');
$t->is($results->numFound, 1,
  '::postInsert() new objects are automaticaly indexed when saved');

$post->title = 'bazbarfoo';
$post->save();

$t->comment('-> postUpdate');
$results = Doctrine::getTable('Post')->search('bazbarfoo');
$t->is($results->numFound, 1,
  '::postUpdate() existing objects are re-indexed when updated');

$results = Doctrine::getTable('Post')->search('foobarbaz');
$t->is($results->numFound, 0,
  '::postUpdate() existing data is cleared from index before reindexing');

$t->comment('-> preDelete');
$post->delete();
$results = Doctrine::getTable('Post')->search('bazbarfoo');
$t->is($results->numFound, 0,
  '::preDelete() objects are removed from index when deleted');
