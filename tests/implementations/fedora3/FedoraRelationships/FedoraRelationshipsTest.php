<?php
require_once 'implementations/RepositoryFactory.php';
require_once 'tests/TestHelpers.php';

/**
 * @todo pull more tests out of tjhe microservices version of these functions
 *  to make sure we handle more cases.
 *
 * @todo remove any calls to StringEqualsXmlString because it uses the
 *  domdocument cannonicalization function that doesn't work properly on cent
 */
class FedoraRelationshipsTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    $this->repository = RepositoryFactory::getRepository('fedora3', new RepositoryConfig(FEDORAURL, FEDORAUSER, FEDORAPASS));
  }

  function testRelationshipDescription() {
    $object = $this->repository->constructObject("test:test");
    $rel = $object->relationships;

    $rel->registerNamespace('fuckyah', 'http://crazycool.com#');
    $rel->add('http://crazycool.com#', 'woot', 'test', TRUE);

    $relationships = $rel->get();

    $this->assertEquals(1, count($relationships));
    $this->assertEquals('fuckyah', $relationships[0]['predicate']['alias']);
    $this->assertEquals('http://crazycool.com#', $relationships[0]['predicate']['namespace']);
    $this->assertEquals('woot', $relationships[0]['predicate']['value']);
    $this->assertTrue($relationships[0]['object']['literal']);
    $this->assertEquals('test', $relationships[0]['object']['value']);
  }

  function testRelationshipLowerD() {
    $content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<RDF xmlns="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fuckyah="http://crazycool.com#">
  <description rdf:about="info:fedora/test:test">
    <fuckyah:woot>test</fuckyah:woot>
  </description>
</RDF>
XML;
    $object = $this->repository->constructObject("test:test");
    $datastream = $object->constructDatastream('RELS-EXT', 'M');
    $datastream->content = $content;
    $object->ingestDatastream($datastream);

    $object->relationships->add('http://crazycool.com#', 'woot', '1234', TRUE);
    $rels = $object->relationships->get();
    $this->assertEquals(2, count($rels));
    $this->assertEquals('test', $rels[0]['object']['value']);
    $this->assertEquals('1234', $rels[1]['object']['value']);
  }

  function testGetFromExistingWithRdf() {
    $content = <<<XML
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#">
  <rdf:Description rdf:about="info:fedora/islandora:479">
    <fedora-model:hasModel rdf:resource="info:fedora/islandora:sp_basic_image"></fedora-model:hasModel>
    <fedora:isMemberOfCollection rdf:resource="info:fedora/islandora:sp_basic_image_collection"></fedora:isMemberOfCollection>
  </rdf:Description>
</rdf:RDF>
XML;
    $object = $this->repository->constructObject("islandora:479");
    $datastream = $object->constructDatastream('RELS-EXT', 'M');
    $datastream->content = $content;
    $object->ingestDatastream($datastream);

    $relations = $object->relationships->get('info:fedora/fedora-system:def/relations-external#', 'isMemberOfCollection');

    $this->assertEquals(1, count($relations));
    $this->assertEquals('islandora:sp_basic_image_collection', $relations[0]['object']['value']);
  }

  function testChangeId() {
$expected = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:fedora="info:fedora/fedora-system:def/relations-external#" xmlns:fedora-model="info:fedora/fedora-system:def/model#" xmlns:islandora="http://islandora.ca/ontology/relsext#" xmlns:fuckyah="http://crazycool.com#">
  <rdf:Description rdf:about="info:fedora/zapp:brannigan">
    <fuckyah:woot>test</fuckyah:woot>
  </rdf:Description>
</rdf:RDF>

XML;
    $object = $this->repository->constructObject("test:test");
    $rel = $object->relationships;

    $rel->registerNamespace('fuckyah', 'http://crazycool.com#');
    $rel->add('http://crazycool.com#', 'woot', 'test', TRUE);
    $rel->changeObjectId('zapp:brannigan');

    $this->assertEquals($expected, $object['RELS-EXT']->content);
  }
}
