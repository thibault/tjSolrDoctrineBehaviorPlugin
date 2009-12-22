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

// Ensure the index is empty
$results = Doctrine::getTable('Post')->deleteIndex();

// Should return no results
$t->comment('-> Search not existing content');
$results = Doctrine::getTable('Post')->search('gloubigoulba');
$t->is($results->numFound, 0);

$t->comment('-> Indexing content');
$post = new Post();
$post->title = 'gloubigoulba';
$post->body = 'this is my body';
$post->Thread = new Thread();
$post->Thread->title = 'test thread';
$post->save();

$otherPost = new Post();
$otherPost->title = 'foobar';
$otherPost->body = 'This is a gloubigoulba body';
$otherPost->Thread = $post->Thread;
$otherPost->save();

$results = Doctrine::getTable('Post')->search('gloubigoulba');
$t->is($results->numFound, 2);

$t->comment('-> Deleting content');
$post->delete();
$results = Doctrine::getTable('Post')->search('gloubigoulba');
$t->is($results->numFound, 1);
