<?php

require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora4/Fedora4Api.php';

class RepositoryTestBase extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    $connection = new RepositoryConnection(FEDORA4URL);
    $this->api = new Fedora4Api($connection);
  }

  public function testAddDatastream() {
    $response = $this->api->m->addDatastream('islandora:root', 'bar', 'string', 'wppt', array('mimeType' => 'text/plain', 'dsLabel' => 'woot'));
    $foo = $this->api->a->getDatastreamDissemination('islandora:root', 'bar');
    print_r($foo);
    print_r($response);
  }

  public function testGetDatastream() {
    $response = $this->api->m->getDatastream('islandora:root', 'bar');
    print_r($response);
  }
}
