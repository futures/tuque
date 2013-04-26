<?php

require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora4/Fedora4Api.php';

class RepositoryTestBase extends PHPUnit_Framework_TestCase {
  protected function setUp() {
    $connection = new RepositoryConnection(FEDORA4URL);
    $this->api = new Fedora4Api($connection);
  }

  public function testDescribe() {
    $response = $this->api->a->describeRepository();
    $this->assertArrayHasKey('repositoryBaseURL', $response);
    $this->assertArrayHasKey('repositoryVersion', $response);
    $this->assertArrayHasKey('numberOfObjects', $response);
    $this->assertArrayHasKey('repositorySize', $response);
  }

  /**
   * @todo make this test something
   */
  public function testFind() {
    $response = $this->api->a->findObjects();
    $this->assertInternalType('string', $response);
  }

  public function testDatastreamDissemination() {
    $foo = $this->api->a->getDatastreamDissemination('islandora:root', 'RELS-EXT');
    print_r($foo);
  }

  public function testDatastreamDisseminationFile() {
    $file = tempnam(sys_get_temp_dir(), "test");
    $this->api->a->getDatastreamDissemination('islandora:root', 'RELS-EXT', NULL, $file);
    print_r(file_get_contents($file));
  }

  public function testGetObjectProfile() {
    $test = $this->api->a->getObjectProfile('islandora:root');
    print_r($test);
  }

  public function testListDatastreams() {
    $test = $this->api->a->listDatastreams('islandora:root');
    print_r($test);
  }
}
