<?php

require_once 'HttpConnection.php';
require_once 'implementations/fedora3/FedoraApi.php';

class Fedora4Api {

  private $url;
  private $connection;
  private $f3api;

  public function  __construct($url, $connection = NULL) {
    $this->url = $url;

    if (!$connection) {
      $connection = new CurlConnection();
    }

    $this->connection = $connection;
    $this->f3api = new FedoraApi(new RepositoryConnection($this->url));
  }

  private function createUrl($request) {
    return $this->url . $request;
  }

  public function describeRepository() {
    $request = "/describe";
    $response = $this->connection->getRequest($this->createUrl($request), array('headers' => array('Accept: application/json')));
    $return = json_decode($response['content'], TRUE);
    return $return['fedoraRepository'];
  }

  public function describeModeshape() {
    $request = "/modeshape";
    $response = $this->connection->getRequest($this->createUrl($request), array('headers' => array('Accept: application/json')));
    $return = json_decode($response['content'], TRUE);
    return $return['repositories'];
  }
}
