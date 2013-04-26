<?php

//require_once 'implementations/fedora4/Repository.php';
//require_once 'implementations/fedora3/RepositoryConnection.php';
//require_once 'implementations/fedora4/FedoraApi.php';
//require_once 'tests/implementations/RepositoryTestBase.php';

class Fedora4RepositoryTest extends RepositoryTestBase {

  protected function setUp() {
  //  if(!defined('FEDORA4URL')) {
      $this->markTestSkipped('Fedora 4 is not configured.');
  //  }

    $connection = new RepositoryConnection(FEDORA4URL);
    $this->api = new FedoraApi($connection);
    $cache = new SimpleCache();
    $this->repository = new FedoraRepository($this->api, $cache);
  }
}