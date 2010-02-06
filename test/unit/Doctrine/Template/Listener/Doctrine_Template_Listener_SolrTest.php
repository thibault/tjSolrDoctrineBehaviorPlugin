<?php

/**
 * Doctrine_Template_Listener_Solr tests.
 */
include_once dirname(__FILE__).'/../../../../bootstrap/bootstrap.php';

LimeAnnotationSupport::enable();
$t = new lime_test(10);

// @Before

$handler = $t->mock('Search_Handler_Interface');

$post = new Post();
$post->setTitle('title');
$post->setBody('body');
$post->Thread = new Thread();
$post->Thread->title = 'test thread';
$post->save();

Doctrine::getTable('Post')->setSearchHandler($handler);

// @After

$handler->reset();
unset($handler);
unset($post);


// @Test: Check if index is updated when a new object is created


$handler->any('unindex')->once();
$handler->commit()->once();
$handler->any('index')->once();
$handler->commit()->once();
$handler->replay();

$post2 = new Post();
$post2->setTitle('title');
$post2->setBody('body');
$post2->Thread = new Thread();
$post2->Thread->title = 'test thread';
$post2->save();

$handler->verify();

// @Test: Check if index is updated when the object is

$handler->any('unindex')->once();
$handler->commit()->once();
$handler->any('index')->once();
$handler->commit()->once();
$handler->replay();

$post->title = 'changed title';
$post->save();

$handler->verify();

// @Test: Check if index is updated when the object is deleted

$handler->any('unindex')->once();
$handler->commit()->once();
$handler->replay();

$post->delete();

$handler->verify();
