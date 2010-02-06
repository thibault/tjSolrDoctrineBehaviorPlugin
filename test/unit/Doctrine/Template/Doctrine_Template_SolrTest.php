<?php

/**
 * Doctrine_Template_Solr tests.
 */
include_once dirname(__FILE__).'/../../../bootstrap/bootstrap.php';

LimeAnnotationSupport::enable();
$t = new lime_test(11);

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

// @Test: template availability

$t->ok(is_callable(array(Doctrine::getTable('Post'), 'isSearchAvailable')));

// @Test: getUniqueId() generates a correct id

$identifier = sprintf("Post_%d", $post->getId());
$t->is($post->getUniqueId(), $identifier);

// @Test: getFieldsArray() generates an array with all the required fields

$keys = array_keys($post->getFieldsArray());
$t->is_deeply($keys, array(
  'sf_unique_id',
  'sf_meta_class',
  'sf_meta_id',
  'title_t',
  'body_t',
));

// @Test: getFieldsArray() generates an array with correct values

$array = $post->getFieldsArray();
$t->is($array['sf_meta_class']['value'], 'Post');
$t->is($array['sf_meta_id']['value'], $post->getId());
$t->is($array['title_t']['value'], 'title');
$t->is($array['body_t']['value'], 'body');

// @Test: deleteIndex() calls the deleteAllFromClass handler function

$handler->deleteAllFromClass('Post')->once();
$handler->commit()->once();
$handler->replay();

Doctrine::getTable('Post')->deleteIndex();

$handler->verify();

// @Test: createSearchQuery returns a Doctrine_Query object

$results = array(
  'response' => array(
    'docs' => array(
      0 => array(
        'sf_meta_id' => 1,
      ),
      3 => array(
        'sf_meta_id' => 2,
      ),
      2 => array(
        'sf_meta_id' => 3,
      )
    )
  )
);
$handler->any('search')->returns($results);
$handler->replay();

$q = Doctrine::getTable('Post')->createSearchQuery('azerty');
$t->ok($q instanceof Doctrine_Query);
