<?php

require_once 'implementations/fedora4/experimental/Fedora4Api.php';

class FedoraApi4Test extends PHPUnit_Framework_TestCase {
  protected $pids = array();
  protected $files = array();

  protected function setUp() {
    if(!defined('FEDORA4URL')) {
      $this->markTestSkipped('Fedora 4 is not configured.');
    }

    $this->api = new Fedora4Api(FEDORA4URL);
  }

  protected function tearDown() {

  }

  public function testDescribeRepository() {
    $response = $this->api->describeRepository();
    $this->assertArrayHasKey('repositoryBaseURL', $response);
    $this->assertArrayHasKey('repositoryVersion', $response);
    $this->assertArrayHasKey('numberOfObjects', $response);
    $this->assertArrayHasKey('repositorySize', $response);
    $this->assertArrayHasKey('sampleOAI-URL', $response);
  }

  public function testDescribeModeshape() {
    $response = $this->api->describeModeshape();
    $this->assertTrue(count($response) > 0);
  }


}