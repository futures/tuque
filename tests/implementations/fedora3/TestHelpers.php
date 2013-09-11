<?php

require_once 'implementations/fedora3/Datastream.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'implementations/fedora3/FedoraApiSerializer.php';
require_once 'implementations/fedora3/Object.php';
require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora3/RepositoryException.php';
require_once 'implementations/fedora3/RepositoryQuery.php';
require_once 'implementations/fedora3/Repository.php';
require_once 'implementations/fedora3/FedoraRelationships.php';
require_once 'includes/SimpleCache.php';

class FedoraTestHelpers {
  static function randomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }

  static function randomCharString($length) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($p = 0; $p < $length; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters)-1))];
    }

    return $string;
  }
}
