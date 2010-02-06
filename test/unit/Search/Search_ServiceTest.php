<?php

/**
 * Search_Service tests.
 */
include_once dirname(__FILE__).'/../../bootstrap/bootstrap.php';

LimeAnnotationSupport::enable();
$t = new lime_test(11);

// @Before

$handler = $t->mock('Search_Handler_Interface');
$service = new Search_Service($handler);

// @After

unset($handler);
unset($service);

// @Test: commit() raise an exception when not in a transaction

$t->expect('sfException');
$service->commit();

// @Test : commit() calls the handler commit function when closing a transaction

$handler->commit()->once();
$handler->replay();

$service->beginTransaction();
$service->commit();

$handler->verify();

// @Test: transactions can be nested

$handler->commit()->never();
$handler->replay();

$service->beginTransaction();
$service->beginTransaction();
$service->commit();

$handler->verify();

$handler->reset();
$handler->commit()->once();
$handler->replay();

$service->commit();

$handler->verify();

// @Test: inTransaction() returns false when no transaction is started

$t->ok(!$service->inTransaction());

// @Test: inTransaction() returns true when a transaction is not committed yet

$service->beginTransaction();
$t->ok($service->inTransaction());

// @Test: inTransaction() returns false when a transaction is commited

$service->beginTransaction();
$service->commit();
$t->ok(!$service->inTransaction());

// @Test: addToIndex raise a commit when not in transaction

$handler->any('index')->once();
$handler->commit()->once();
$handler->replay();

$service->addToIndex(new Post());

$handler->verify();


// @Test: addToIndex raise no commit when in transaction
// We could also write the same test for deleteFromIndex and deleteIndex, but what a waste of time

$handler->any('index')->once();
$handler->commit()->never();
$handler->replay();

$service->beginTransaction();
$service->addToIndex(new Post());

$handler->verify();


