<?php

/**
 * @file
 * A set of test classes that test the implementations/fedora3/FedoraApi.php file
 */
require_once 'RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

class FedoraApi4FindObjectsTest extends PHPUnit_Framework_TestCase {

  public $apim;
  public $apia;
  public $namespace;
  public $fixtures;
  public $display;
  public $pids;
  static $purge = true;
  static $saved;

  protected function sanitizeObjectProfile($profile) {
    $profile['objDissIndexViewURL'] = parse_url($profile['objDissIndexViewURL'], PHP_URL_PATH);
    $profile['objItemIndexViewURL'] = parse_url($profile['objItemIndexViewURL'], PHP_URL_PATH);
    return $profile;
  }

  protected function setUp() {
    $connection = new RepositoryConnection(new RepositoryConfig('http://localhost:8080/rest'));
    $serializer = new Fedora4ApiSerializer();

    $this->apim = new Fedora4ApiM($connection, $serializer);
    $this->apia = new Fedora4ApiA($connection, $serializer);

    if (self::$purge == FALSE) {
      $this->fixtures = self::$saved;
      return;
    }

    $this->namespace = 'test';
    //$this->namespace = FedoraTestHelpers::randomString(10);
    $pid1 = $this->namespace . ":" . FedoraTestHelpers::randomString(10);
    $pid2 = $this->namespace . ":" . FedoraTestHelpers::randomString(10);

    $this->fixtures = array();
    $this->pids = array();
    $this->pids[] = $pid1;
    $this->pids[] = $pid2;

    // Set up some arrays of data for the fixtures.
    $string = file_get_contents('tests/test_data/fixture1.xml');
    $string = preg_replace('/\%PID\%/', $pid1, $string);
    $pid = $this->apim->ingest(array('string' => $string));
    $urlpid = urlencode($pid);
    $this->fixtures[$pid] = array();
    $this->fixtures[$pid]['xml'] = $string;
    $this->fixtures[$pid]['findObjects'] = array('pid' => $pid1,
      'label' => 'label1', 'state' => 'I', 'ownerId' => 'owner1',
      'cDate' => '2012-03-12T15:22:37.847Z', 'dcmDate' => '2012-03-13T14:12:59.272Z',
      'title' => 'title1', 'creator' => 'creator1', 'subject' => 'subject1',
      'description' => 'description1', 'publisher' => 'publisher1',
      'contributor' => 'contributor1', 'date' => 'date1', 'type' => 'type1',
      'format' => 'format1',
      //'identifier' => $pid,
      'source' => 'source1',
      'language' => 'language1', 'relation' => 'relation1', 'coverage' => 'coverage1',
      'rights' => 'rights1',
    );
    $this->fixtures[$pid]['getObjectHistory'] = array('2012-03-13T14:12:59.272Z',
      '2012-03-13T17:40:29.057Z', '2012-03-13T18:09:25.425Z',
      '2012-03-13T19:15:07.529Z');
    $this->fixtures[$pid]['getObjectProfile'] = array(
      'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
      'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
      'objModels' => array('info:fedora/fedora-system:FedoraObject-3.0'),
      'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
      'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
      'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
      'objState' => $this->fixtures[$pid]['findObjects']['state'],
    );
    $this->fixtures[$pid]['listDatastreams'] = array(
      '2012-03-13T14:12:59.272Z' => array(
        'DC' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/octet-stream',
        ),
      ),
      '2012-03-13T17:40:29.057Z' => array(
        'DC' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
        'fixture' => Array(
          'label' => 'Default Label',
          'mimetype' => 'image/png',
        ),
      ),
      '2012-03-13T18:09:25.425Z' => Array(
        'DC' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
        'fixture' => Array(
          'label' => 'Default Label',
          'mimetype' => 'image/png',
        ),
      ),
      '2012-03-13T19:15:07.529Z' => Array(
        'DC' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
        'fixture' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
        'RELS-EXT' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
      ),
    );
    $this->fixtures[$pid]['dsids'] = array(
      'DC' => array(
        'data' => array(
          'dsLabel' => 'Dublin Core Record for this object',
          'dsVersionID' => 'DC.1',
          'dsCreateDate' => '2012-03-13T14:12:59.272Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
          'dsControlGroup' => 'X',
          'dsSize' => '860',
          'dsVersionable' => 'true',
          'dsInfoType' => '',
          'dsLocation' => "$pid+DC+DC.1",
          'dsLocationType' => '',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
      'fixture' => array(
        'data' => array(
          'dsLabel' => 'Default Label',
          'dsVersionID' => 'fixture.4',
          'dsCreateDate' => '2012-03-13T18:09:25.425Z',
          'dsState' => 'A',
          'dsMIME' => 'image/png',
          'dsFormatURI' => 'format',
          'dsControlGroup' => 'M',
          'dsSize' => '68524',
          'dsVersionable' => 'true',
          'dsInfoType' => '',
          'dsLocation' => "$pid+fixture+fixture.4",
          'dsLocationType' => 'INTERNAL_ID',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 2,
      ),
      'RELS-EXT' => array(
        'data' => array(
          'dsLabel' => 'Fedora Relationships Metadata',
          'dsVersionID' => 'RELS-EXT.0',
          'dsCreateDate' => '2012-03-13T19:15:07.529Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => '',
          'dsControlGroup' => 'X',
          'dsSize' => '540',
          'dsVersionable' => 'true',
          'dsInfoType' => '',
          'dsLocation' => "$pid+RELS-EXT+RELS-EXT.0",
          'dsLocationType' => 'INTERNAL_ID',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
    );

    // second fixture
    $string = file_get_contents('tests/test_data/fixture2.xml');
    $pid = $this->apim->ingest(array('pid' => $pid2, 'string' => $string));
    $urlpid = urlencode($pid);
    $this->fixtures[$pid] = array();
    $this->fixtures[$pid]['xml'] = $string;
    $this->fixtures[$pid]['findObjects'] = array(
      'pid' => $pid,
      'label' => 'label2',
      'state' => 'A',
      'ownerId' => 'owner2',
      'cDate' => '2000-03-12T15:22:37.847Z',
      'dcmDate' => '2010-03-13T14:12:59.272Z',
      'title' => 'title2',
      'creator' => 'creator2',
      'subject' => 'subject2',
      'description' => 'description2',
      'publisher' => 'publisher2',
      'contributor' => 'contributor2',
      'date' => 'date2',
      'type' => 'type2',
      'format' => 'format2',
      //'identifier' => array('identifier2', $pid),
      'source' => 'source2',
      'language' => 'language2',
      'relation' => 'relation2',
      'coverage' => 'coverage2',
      'rights' => 'rights2',
    );
    $this->fixtures[$pid]['getObjectHistory'] = array('2010-03-13T14:12:59.272Z');
    $this->fixtures[$pid]['getObjectProfile'] = array(
      'objLabel' => $this->fixtures[$pid]['findObjects']['label'],
      'objOwnerId' => $this->fixtures[$pid]['findObjects']['ownerId'],
      'objModels' => array('info:fedora/fedora-system:FedoraObject-3.0'),
      'objCreateDate' => $this->fixtures[$pid]['findObjects']['cDate'],
      'objDissIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewMethodIndex",
      'objItemIndexViewURL' => "http://localhost:8080/fedora/objects/$urlpid/methods/fedora-system%3A3/viewItemIndex",
      'objState' => $this->fixtures[$pid]['findObjects']['state'],
    );
    $this->fixtures[$pid]['listDatastreams'] = array(
      '2010-03-13T14:12:59.272Z' => array(
        'DC' => Array(
          'label' => 'Default Label',
          'mimetype' => 'application/rdf+xml',
        ),
      ),
    );
    $this->fixtures[$pid]['dsids'] = array(
      'DC' => array(
        'data' => array(
          'dsLabel' => 'Dublin Core Record for this object',
          'dsVersionID' => 'DC.1',
          'dsCreateDate' => '2010-03-13T14:12:59.272Z',
          'dsState' => 'A',
          'dsMIME' => 'text/xml',
          'dsFormatURI' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
          'dsControlGroup' => 'X',
          'dsSize' => '905',
          'dsVersionable' => 'true',
          'dsInfoType' => '',
          'dsLocation' => "$pid+DC+DC.1",
          'dsLocationType' => '',
          'dsChecksumType' => 'DISABLED',
          'dsChecksum' => 'none',
        ),
        'count' => 1,
      ),
    );

    $this->display = array('pid', 'label', 'state', 'ownerId', 'cDate', 'mDate',
      'dcmDate', 'title', 'creator', 'subject', 'description', 'publisher',
      'contributor', 'date', 'type', 'format', 'identifier', 'source',
      'language', 'relation', 'coverage', 'rights'
    );
  }

  protected function tearDown() {
    if (self::$purge) {
      foreach ($this->fixtures as $key => $value) {
        try {
          $this->apim->purgeObject($key);
        } catch (RepositoryException $e) {
          
        }
      }
    } else {
      self::$saved = $this->fixtures;
    }
  }

  public function testDescribeRepository() {
    $describe = $this->apia->describeRepository();
    $this->assertArrayHasKey('repositoryVersion', $describe);
    $this->assertEquals($describe['repositoryVersion'], '4.0.0');
  }

  // This one is interesting because the flattendocument function doesn't
  // work on it. So we have to handparse it. So we test to make sure its okay.
  // @todo Test the second arguement to this

  function testGetObjectProfile() {
    foreach ($this->fixtures as $pid => $fixture) {
      $expected = $fixture['getObjectProfile'];
      $actual = $this->apia->getObjectProfile($pid);
      // The content models come back in an undefined order, so we need
      // to test them individually.
      $this->assertArrayHasKey('objModels', $actual);
      $this->assertArrayHasKey('objLastModDate', $actual);
      $this->assertArrayHasKey('objCreateDate', $actual);
      $this->assertArrayHasKey('objState', $actual);
      $this->assertArrayHasKey('objLabel', $actual);
      $this->assertArrayHasKey('objOwnerId', $actual);
    }
  }

  function testListDatastreams() {
    foreach ($this->fixtures as $pid => $fixture) {
      $revisions = count($fixture['getObjectHistory']);
      $date = $fixture['getObjectHistory'][$revisions - 1];
      $actual = $this->apia->listDatastreams($pid);
      $this->assertEquals($fixture['listDatastreams'][$date], $actual);
    }
  }

  function testListDatastreamsWithTransaction() {
    $string1 = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string1";
    $txid = $this->apim->addTransaction();
    $actual_pid = $this->apim->ingest(array('pid' => "test:$string1", 'txID' => $txid));
    $datastreams = $this->apia->listDatastreams($actual_pid, NULL, array('txID' => $txid));
    $expected_datastream = Array(
      'DC' => Array
        (
        'label' => 'Default Label',
        'mimetype' => 'application/rdf+xml'
      )
    );
    $this->assertEquals($expected_datastream, $datastreams);
    $this->apim->rollbackTransaction($txid);
  }

  function testGetDatastream() {
    foreach ($this->fixtures as $pid => $fixture) {
      $listDatastreams = $fixture['listDatastreams'];

      // Do a test with the data we have.
      foreach ($listDatastreams as $time => $datastreams) {
        foreach ($datastreams as $dsid => $data) {
          $actual = $this->apim->getDatastream($pid, $dsid, array('asOfDateTime' => $time));
          $this->assertEquals($data['label'], $actual['dsLabel']);
          //         $this->assertEquals($data['mimetype'], $actual['dsMIME']);
          $this->assertArrayHasKey('dsVersionID', $actual);
          $this->assertArrayHasKey('dsCreateDate', $actual);
          $this->assertArrayHasKey('dsState', $actual);
          //  $this->assertArrayHasKey('dsMIME', $actual);
          $this->assertArrayHasKey('dsFormatURI', $actual);
          $this->assertArrayHasKey('dsControlGroup', $actual);
          //  $this->assertArrayHasKey('dsSize', $actual);
          $this->assertArrayHasKey('dsVersionable', $actual);
          $this->assertArrayHasKey('dsInfoType', $actual);
          $this->assertArrayHasKey('dsLocation', $actual);
          $this->assertArrayHasKey('dsLocationType', $actual);
          //  $this->assertArrayHasKey('dsChecksumType', $actual);
          //  $this->assertArrayHasKey('dsChecksum', $actual);
        }
      }

      // Test with the more detailed current data.
      foreach ($fixture['dsids'] as $dsid => $data) {
        $actual = $this->apim->getDatastream($pid, $dsid);
        //       $this->assertEquals($data['data'], $actual);
      }
    }
  }

  function testGetDatastreamWithTransaction() {
    $string1 = FedoraTestHelpers::randomString(10);
    $expected_pid = "test:$string1";
    $txid = $this->apim->addTransaction();
    $actual_pid = $this->apim->ingest(array('pid' => "test:$string1", 'txID' => $txid));
    $datastream = $this->apim->getDatastream($actual_pid, 'DC', array('txID' => $txid));
    $expected_datastream = array(
      'dsLabel' => 'Default Label',
      'dsVersionID' => 'DC.0',
      'dsState' => 'A',
      'dsFormatURI' => '',
      'dsControlGroup' => 'M',
      'dsVersionable' => '',
      'dsInfoType' => '',
      'dsLocation' => '',
      'dsLocationType' => '',
      'dsLogMessage' => '',
      'dsMIME' => 'application/rdf+xml');
    $this->assertEquals($expected_datastream, $datastream);
    $this->apim->rollbackTransaction($txid);
  }

  function testAddRelationship() {
    foreach ($this->fixtures as $pid => $fixture) {
      $predicate = FedoraTestHelpers::randomString(10);
      $object = FedoraTestHelpers::randomString(10);
      $relationship = array('predicate' => $predicate, 'object' => $object);
      $relationships = $this->apim->addRelationship($pid, $relationship);
      $relationship_array = array(
        $predicate => "info:fedora/$object",
      );
      $this->assertContains($relationship_array, $relationships);
    }
  }

}
