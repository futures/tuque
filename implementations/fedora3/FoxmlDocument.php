<?php
set_include_path("sites/all/libraries/tuque/");
class FoxmlDocument extends DOMDocument {
  const FOXML = 'info:fedora/fedora-system:def/foxml#';
  const xlink = 'http://www.w3.org/1999/xlink';
  const xsi = 'http://www.w3.org/2001/XMLSchema-instance';
  const xmlns = 'http://www.w3.org/2000/xmlns/';
  const rdf = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
  const rdfs = 'http://www.w3.org/2000/01/rdf-schema#';
  const fedora = 'info:fedora/fedora-system:def/relations-external#';
  const dc = 'http://purl.org/dc/elements/1.1/';
  const oai_dc = 'http://www.openarchives.org/OAI/2.0/oai_dc/';
  const fedora_model = 'info:fedora/fedora-system:def/model#';

  protected $root;

  public static function fromObject(AbstractObject $object) {
    $foxml_document = new self();
    $foxml_document->createRootElementFromObject($object);
    $foxml_document->createDocumentFromObject($object);
    return $foxml_document;
  }

  public static function fromString($string) {
    $foxml_document = new self();
    $foxml_document->loadXML($string);
    return $foxml_document;
  }

  public static function fromDOMDocument(DOMDocument $doc) {
    return self::fromString($doc->saveXML());
  }

  public function __construct() {
    parent::__construct("1.0", "UTF-8"); // DomDocument
    $this->formatOutput = TRUE;
    $this->preserveWhiteSpace = FALSE;
  }

  private function createRootElementFromObject(AbstractObject $object) {
    $root = $this->createElementNS(self::FOXML, 'foxml:digitalObject');
    $root->setAttribute('VERSION', '1.1');
    $root->setAttribute('PID', "{$object->id}");
    $root->setAttributeNS(self::xmlns, 'xmlns', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:foxml', self::FOXML);
    $root->setAttributeNS(self::xmlns, 'xmlns:xsi', self::xsi);
    $root->setAttributeNS(self::xsi, 'xsi:schemaLocation', self::FOXML . " http://www.fedora.info/definitions/1/0/foxml1-1.xsd");
    $this->appendChild($root);
    $this->root = $root;
  }

  private function createDocumentFromObject(AbstractObject $object) {
    // If DOMNodes are not appended in the corrected order root -> leaf,
    // namespaces may break... So be be cautious, add DOMNodes to their
    // parent element before adding child elements to them.
    $this->createObjectProperties($object);
    $this->createDocumentDatastreams($object);
  }

  private function createObjectProperties(AbstractObject $object) {
    $object_properties = $this->createElementNS(self::FOXML, 'foxml:objectProperties');
    $this->root->appendChild($object_properties);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#state');
    $property->setAttribute('VALUE', $object->state);
    $object_properties->appendChild($property);

    $property = $this->createElementNS(self::FOXML, 'foxml:property');
    $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#label');
    $property->setAttribute('VALUE', $object->label);
    $object_properties->appendChild($property);

    if (isset($object->owner)) {
      $property = $this->createElementNS(self::FOXML, 'foxml:property');
      $property->setAttribute('NAME', 'info:fedora/fedora-system:def/model#ownerId');
      $property->setAttribute('VALUE', $object->owner);
      $object_properties->appendChild($property);
    }
    return $object_properties;
  }

  private function createDatastreamElement($id = NULL, $state = NULL, $control_group = NULL, $versionable = NULL) {
    $datastream = $this->createElementNS(self::FOXML, 'foxml:datastream');
    if (isset($id)) {
      $datastream->setAttribute('ID', $id);
    }
    if (isset($state)) {
      $datastream->setAttribute('STATE', $state);
    }
    if (isset($control_group)) {
      $datastream->setAttribute('CONTROL_GROUP', $control_group);
    }
    if (isset($versionable)) {
      $datastream->setAttribute('VERSIONABLE', $versionable ? 'true' : 'false');
    }
    return $datastream;
  }

  private function createDatastreamVersionElement($id = NULL, $label = NULL, $mime_type = NULL, $format_uri = NULL) {
    $version = $this->createElementNS(self::FOXML, 'foxml:datastreamVersion');
    if (isset($id)) {
      $version->setAttribute('ID', $id);
    }
    if (isset($label)) {
      $version->setAttribute('LABEL', $label);
    }
    if (isset($mime_type)) {
      $version->setAttribute('MIMETYPE', $mime_type);
    }
    if (isset($format_uri)) {
      $version->setAttribute('FORMAT_URI', $format_uri);
    }
    return $version;
  }

  private function createDatastreamDigestElement($type = NULL, $checksum = NULL) {
    $digest = $this->createElementNS(self::FOXML, 'foxml:contentDigest');
    if (isset($type)) {
      $digest->setAttribute('TYPE', $type);
    }
    if (isset($digest)) {
      $digest->setAttribute('DIGEST', $checksum);
    }
    return $digest;
  }

  private function createDatastreamContentElement() {
    $content = $this->createElementNS(self::FOXML, 'foxml:xmlContent');
    return $content;
  }

  private function createDatastreamContentLocationElement($type = NULL, $ref = NULL) {
    $location = $this->createElementNS(self::FOXML, 'foxml:contentLocation');
    if (isset($type)) {
      $location->setAttribute('TYPE', $type);
    }
    if (isset($ref)) {
      $location->setAttribute('REF', $ref);
    }
    return $location;
  }

  /**
   * Passes each datastream to the appropriate ds create function.
   */
  private function createDocumentDatastreams(AbstractObject $object) {
    foreach ($object as $ds) {
      switch ($ds->controlGroup) {
        case 'X':
          $this->createInlineDocumentDatastream($ds);
          break;

        default:
          $this->createDocumentDatastream($ds);
          break;
      }
    }
  }

  /**
   * Creates FOXML for any inline datastreams based on the information passed in the $ds object.
   *
   * @param object $ds
   *   The datastream object
   */
  private function createInlineDocumentDatastream($ds) {
    $datastream = $this->createDatastreamElement($ds->id, $ds->state, $ds->controlGroup, $ds->versionable);
    $version = $this->createDatastreamVersionElement("{$ds->id}.0", $ds->label, $ds->mimetype, $ds->format);
    $content = $this->createDatastreamContentElement();
    $xml_dom = new DOMDocument();
    if (!$xml_dom->loadXML($ds->content)) {
      throw new RepositoryXmlError("{$ds->id} on {$ds->parent->id} contains invalid XML");
    }
    $child = $this->importNode($xml_dom->documentElement, TRUE);
    $version_node = $this->root->appendChild($datastream)->appendChild($version);
    if (isset($ds->checksumType)) {
      $digest = $this->createDatastreamDigestElement($ds->checksumType, $ds->checksum);
      $version_node->appendChild($digest);
    }
    $version_node->appendChild($content)->appendChild($child);
    $simple_dom = simplexml_import_dom($xml_dom);
    $namespaces = $simple_dom->getDocNamespaces(TRUE);
    foreach ($namespaces as $prefix => $uri) {
      if ($prefix) {
        $child->setAttributeNS(self::xmlns, "xmlns:$prefix", $uri);
      }
    }
  }

  /**
   * Creates FOXML for any managed, externally referenced or redirect datastreams bases on the $ds object
   *
   * @param object $ds
   *   The datastream object
   */
  private function createDocumentDatastream($ds) {
    $datastream = $this->createDatastreamElement($ds->id, $ds->state, $ds->controlGroup, $ds->versionable);
    $version = $this->createDatastreamVersionElement($ds->id . '.0', $ds->label, $ds->mimetype, $ds->format);
    $content = $this->createDatastreamContentLocationElement('URL', $ds->content);
    $version_node = $this->root->appendChild($datastream)->appendChild($version);
    if (isset($ds->checksumType)) {
      $digest = $this->createDatastreamDigestElement($ds->checksumType, $ds->checksum);
      $version_node->appendChild($digest);
    }
    $version_node->appendChild($content);
  }
}
