<?php

/**
 * Doctrine_Template_Solr tests.
 */
include dirname(__FILE__).'/../../../bootstrap/bootstrap.php';

$t = new lime_test(8);

// We need access to Solr to run our tests. Ensure it is running
if(!Doctrine::getTable('Post')->isSearchAvailable())
  die();

// Ensure we're working on a clean index
Doctrine::getTable('Post')->deleteIndex();
Doctrine::loadData(dirname(__FILE__).'/../../../fixtures/project/data/fixtures');
$results = Doctrine::getTable('Post')->search('*:*');
$numResults = $results->numFound;
$post = Doctrine_Core::getTable('Post')
  ->createQuery('p')
  ->fetchOne();

$t->comment('-> Template availability');
$t->ok(is_callable(array(Doctrine::getTable('Post'), 'isSearchAvailable')),
  'Templates function are available');

$t->comment('-> getSolrId');
$t->is($post->getSolrId(), sprintf('Post_%d', $post->getId()),
  '::getSolrId() generates a correct identifier');

$t->comment('-> deleteFromIndex');
$post->deleteFromIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, $numResults - 1,
  '::deleteFromIndex() correctly removes object from solr');

$t->comment('-> addToIndex');
$post->addToIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, $numResults,
  '::addFromIndex() correctly adds object to solr');

$t->comment('-> deleteIndex');
Doctrine::getTable('Post')->deleteIndex();
$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, 0,
  '::deleteIndex() leaves an empty index');

$t->comment('-> search');

$results = Doctrine::getTable('Post')->search('*:*');
$t->is($results->numFound, 0,
  '::search() returns correct result number when empty index');

$results = Doctrine::getTable('Post')->search('azerty');
$t->is($results->numFound, 0,
  '::search() returns no answer for a random unexisting word');

$post = new Post();
$post->title = 'azerty';
$post->body = 'this is my body';
$post->Thread = new Thread();
$post->Thread->title = 'test thread';
$post->save();

$otherPost = new Post();
$otherPost->title = 'foobar';
$otherPost->body = 'This is an azerty body';
$otherPost->Thread = $post->Thread;
$otherPost->save();

$results = Doctrine::getTable('Post')->search('azerty');
$t->is($results->numFound, 2,
  '::search() words are found in every fields');
