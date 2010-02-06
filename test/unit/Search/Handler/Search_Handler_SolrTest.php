<?php

/**
 * Search_Handler_Solr tests.
 *
 * As this class is just a bridge, we won't unit test the trivial methods
 */
include_once dirname(__FILE__).'/../../../bootstrap/bootstrap.php';

LimeAnnotationSupport::enable();
$t = new lime_test(5);

// @Before

$handler = new Search_Handler_Solr('localhost', '8983', '/solr');
$documentArray = array(
  'title' => array(
    'value' => 'my title',
    'boost' => 2.33
  ),
  'body' => array(
    'value' => 'my body',
    'boost' => 1
  )
);
$document = $handler->buildDocument($documentArray);

// @After

unset($handler);
unset($documentArray);
unset($document);

// @Test: buildDocument() returns an Apache_Solr_Document

$t->is(get_class($document), "Apache_Solr_Document");

// @Test: buildDocument() returns a correct Solr_Document

$field = $document->getField('title');
$t->is($field['value'][0], "my title");

// @Test: buildDocument() set a correct boost value

$t->is($document->getFieldBoost('title'), 2.33);

// @Test: search() Check that the result correspond to the Solr value

$result = $handler->search('*:*');
$t->ok(is_array($result));
$t->ok(isset($result['response']));
