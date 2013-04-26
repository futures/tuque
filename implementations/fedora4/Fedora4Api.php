<?php

/**
 * @file
 * The RAW API wrappers for the Fedora interface.
 */
require_once 'RepositoryException.php';
require_once 'implementations/fedora3/RepositoryConnection.php';
require_once 'implementations/fedora4/FedoraApiSerializer.php';

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class Fedora4Api {

  /**
   * Fedora APIA Class
   * @var FedoraApiA
   */
  public $a;

  /**
   * Fedora APIM Class
   * @var FedoraApiM
   */
  public $m;
  public $connection;

  /**
   * Constructor for the FedoraApi object.
   *
   * @param RepositoryConnection $connection
   *   (Optional) If one isn't provided a default one will be used.
   * @param FedoraApiSerializer $serializer
   *   (Optional) If one isn't provided a default will be used.
   */
  public function __construct(RepositoryConnection $connection = NULL, FedoraApiSerializer $serializer = NULL) {
    if (!$connection) {
      $connection = new RepositoryConnection();
    }

    if (!$serializer) {
      $serializer = new FedoraApiSerializer();
    }

    $this->a = new Fedora4ApiA($connection, $serializer);
    $this->m = new Fedora4ApiM($connection, $serializer);

    $this->connection = $connection;
  }

}


class Fedora4ApiA {

  protected $connection;
  protected $serializer;

  /**
   * Constructor for the new FedoraApiA object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, FedoraApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  public function describeRepository() {
    // This is weird and undocumented, but its what the web client does.
    $request = "/describe";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'xml', 'true');

    $response = $this->connection->getRequest($request, array('headers' => array('Accept: application/json')));
    $return = json_decode($response['content'], TRUE);
    return $return;
  }

  /**
   * @todo test this
   */
  public function getRepositoryVersion(){
    static $version;
    if(empty($version)){
       $info = $this->describeRepository();
       $version = $info['repositoryVersion'];
    }
    return $version;
  }

  /**
   * @todo test this.
   */
  public function getEventsFeed() {
    $version  = $this->getRepositoryVersion();
    if ($version >= 4.0) {
      $request = "/rss";
      $response = $this->connection->getRequest($request, NULL, NULL, 'application/rss+xml');
      $response = $this->serializer->getEventsFeed($response);
      return $response;
    } else {
      throw new Exception( 'Method not supported in versions earlier then 4.0');
    }
  }

  public function findObjects() {
    $request = "/objects";
    $response = $this->connection->getRequest($request, array('headers' => array('Accept: application/json')));
    return $response['content'];
  }

  /**
   * @todo no concept of history
   */
  public function getDatastreamDissemination($pid, $dsid, $as_of_date_time = NULL, $file = NULL) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $seperator = '?';

    $request = "/objects/$pid/datastreams/$dsid/content";
    $response = $this->connection->getRequest($request, array('file' => $file));
    if($file) {
      return TRUE;
    }
    else {
      return $response['content'];
    }
  }

  /**
   * @todo no versioning anymore
   */
  public function getObjectProfile($pid) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}";

    $response = $this->connection->getRequest($request, array('headers' => array('Accept: application/json')));
    $return = json_decode($response['content'], TRUE);
    $return['objModels'] = $return['objModels']['model'];
    return $return;
  }

  /**
   * @todo no adofdatetime
   */
  public function listDatastreams($pid) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/datastreams";

    $response = $this->connection->getRequest($request, array('headers' => array('Accept: application/json')));
    $datastreams = json_decode($response['content'], TRUE);
    $return = array();

    foreach ($datastreams['datastream'] as $ds) {
      $return[$ds['@dsid']] = array('label' => $ds['@label'], 'mimetype' => $ds['@mimeType']);
    }

    return $return;
  }
}


class Fedora4ApiM {

  public function __construct(RepositoryConnection $connection, FedoraApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  public function addDatastream($pid, $dsid, $type, $file, $params) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/$pid/datastreams/$dsid";
    $seperator = '?';

    switch (strtolower($type)) {
      case 'file':
      case 'string':
        break;

      case 'url':
        $this->connection->addParam($request, $seperator, 'dsLocation', $file);
        $type = 'none';
        break;

      default:
        throw new RepositoryBadArguementException("Type must be one of: file, string, url. ($type)");
        break;
    }

    $this->connection->addParamArray($request, $seperator, $params, 'controlGroup');
    $this->connection->addParamArray($request, $seperator, $params, 'altIDs');
    $this->connection->addParamArray($request, $seperator, $params, 'dsLabel');
    $this->connection->addParamArray($request, $seperator, $params, 'versionable');
    $this->connection->addParamArray($request, $seperator, $params, 'dsState');
    $this->connection->addParamArray($request, $seperator, $params, 'formatURI');
    $this->connection->addParamArray($request, $seperator, $params, 'checksumType');
    $this->connection->addParamArray($request, $seperator, $params, 'checksum');
    $this->connection->addParamArray($request, $seperator, $params, 'mimeType');
    $this->connection->addParamArray($request, $seperator, $params, 'logMessage');
    //pp changed this was a post
    $response = $this->connection->putRequest($request, $type, $file, $params['mimeType']);

    //pp changed this as it was expecting xml but we have an array
    //$response = $this->serializer->addDatastream($response);
    return $response;
  }

  /**
   * Returns information about the datastream.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - asOfDateTime: Indicates that the result should be relative to the
   *     digital object as it existed on the given date.
   *   - validateChecksum: verifies that the Datastream content has not changed
   *     since the checksum was initially computed.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array containing information about the datastream. This may also
   *   contains a key dsAltID which contains alternate ids if any are specified.
   *   @code
   *   Array
   *   (
   *       [dsLabel] =>
   *       [dsVersionID] => test.3
   *       [dsCreateDate] => 2012-03-07T18:03:38.679Z
   *       [dsState] => A
   *       [dsMIME] => text/xml
   *       [dsFormatURI] =>
   *       [dsControlGroup] => M
   *       [dsSize] => 22
   *       [dsVersionable] => true
   *       [dsInfoType] =>
   *       [dsLocation] => islandora:strict_pdf+test+test.3
   *       [dsLocationType] => INTERNAL_ID
   *       [dsChecksumType] => DISABLED
   *       [dsChecksum] => none
   *       [dsChecksumValid] => true
   *   )
   *   @endcode
   *
   */
  public function getDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/$pid/datastreams/$dsid";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'format', 'xml');
    $this->connection->addParamArray($request, $seperator, $params, 'asOfDateTime');
    $this->connection->addParamArray($request, $seperator, $params, 'validateChecksum');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastream($response);
    return $response;
  }

  /**
   * FCREPO 4 has an addDatastreams end point where we can send multiple datastreams
   * as part of a multi part POST request.  If any one of the datastreams cannot be
   * added none of them will be added.
   *
   *
   * @param array $datastreams
   *   the datastreams to add
   */
  public function addDatastreams($pid, $datastreams) {
    $request = "/objects/$pid/datastreams";
    $data = array();
    $files = array();
    foreach ($datastreams as $datastream) {
      //$data_array = array();
      if ($datastream->controlGroup == 'M') {
        $files[] = $datastream->content;
        $data[$datastream->id] = '@' . $datastream->content;
      }
      else {
        $data[$datastream->id] = $datastream->content;
      }
      //$data[ $datastream->id] = $data_array;
    }
    $response = $this->connection->postRequest($request, 'datastreams', $data);
    foreach ($files as $file) {
      unlink($file);
    }
  }

  /**
   * Add a RDF relationship to a Fedora object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $relationship
   *   An array containing the subject, predicate and object for the
   *   relationship.
   *   - subject: (optional) Subject of the relationship. Either a URI for the
   *     object or one of its datastreams. If none is given then the URI for
   *     the current object is used.
   *   - predicate: Predicate of the relationship.
   *   - object: Object of the relationship.
   * @param boolean $is_literal
   *   true if the object of the relationship is a literal, false if it is a URI
   * @param string $datatype
   *   (optional) if the object is a literal, the datatype of the literal.
   *
   * @throws RepositoryException
   *
   * @see FedoraApiM::getRelationships
   * @see FedoraApiM::purgeRelationships
   */
  public function addRelationship($pid, $relationship, $is_literal, $datatype = NULL) {
    if (!isset($relationship['predicate'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a predicate element');
    }
    if (!isset($relationship['object'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a object element');
    }

    $pid = urlencode($pid);
    $request = "/objects/$pid/relationships/new";
    $seperator = '?';

    $this->connection->addParamArray($request, $seperator, $relationship, 'subject');
    $this->connection->addParamArray($request, $seperator, $relationship, 'predicate');
    $this->connection->addParamArray($request, $seperator, $relationship, 'object');
    $this->connection->addParam($request, $seperator, 'isLiteral', $is_literal);
    $this->connection->addParam($request, $seperator, 'datatype', $datatype);

    $response = $this->connection->postRequest($request);
    $response = $this->serializer->addRelationship($response);
  }

  /**
   * Export a Fedora object with the given PID.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - format: The XML format to export. One of
   *     info:fedora/fedora-system:FOXML-1.1 (default),
   *     info:fedora/fedora-system:FOXML-1.0,
   *     info:fedora/fedora-system:METSFedoraExt-1.1,
   *     info:fedora/fedora-system:METSFedoraExt-1.0,
   *     info:fedora/fedora-system:ATOM-1.1,
   *     info:fedora/fedora-system:ATOMZip-1.1
   *   - context: The export context, which determines how datastream URLs and
   *     content are represented. Options: public (default), migrate, archive.
   *   - encoding: The preferred encoding of the exported XML.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containing the requested XML.
   */
  public function export($pid, $params = array()) {
    $pid = urlencode($pid);
    $request = "/objects/$pid/export";
    $seperator = '?';

    $this->connection->addParamArray($request, $seperator, $params, 'context');
    $this->connection->addParamArray($request, $seperator, $params, 'format');
    $this->connection->addParamArray($request, $seperator, $params, 'encoding');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->export($response);
    return $response;
  }

  /**
   * Get information on the different versions of a datastream that are
   * avilable in Fedora.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   *
   * @throws RepositoryException
   *
   * @return array
   *   Returns a indexed array with the same keys as getDatastream.
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [dsLabel] =>
   *               [dsVersionID] => test.3
   *               [dsCreateDate] => 2012-03-07T18:03:38.679Z
   *               [dsState] => A
   *               [dsMIME] => text/xml
   *               [dsFormatURI] =>
   *               [dsControlGroup] => M
   *               [dsSize] => 22
   *               [dsVersionable] => true
   *               [dsInfoType] =>
   *               [dsLocation] => islandora:strict_pdf+test+test.3
   *               [dsLocationType] => INTERNAL_ID
   *               [dsChecksumType] => DISABLED
   *               [dsChecksum] => none
   *           )
   *
   *       [1] => Array
   *           (
   *               [dsLabel] =>
   *               [dsVersionID] => test.2
   *               [dsCreateDate] => 2012-03-07T18:03:13.722Z
   *               [dsState] => A
   *               [dsMIME] => text/xml
   *               [dsFormatURI] =>
   *               [dsControlGroup] => M
   *               [dsSize] => 22
   *               [dsVersionable] => true
   *               [dsInfoType] =>
   *               [dsLocation] => islandora:strict_pdf+test+test.2
   *               [dsLocationType] => INTERNAL_ID
   *               [dsChecksumType] => DISABLED
   *               [dsChecksum] => none
   *           )
   *
   *   )
   *   @endcode
   *
   * @see FedoraApiM::getDatastream
   */
  public function getDatastreamHistory($pid, $dsid) {
    //pp changed history not supported in fcrepo 4
    //would like to to a repository version check here
    return array();
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/{$pid}/datastreams/{$dsid}/history";
    $seperator = '?';
    $this->connection->addParam($request, $seperator, 'format', 'xml');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getDatastreamHistory($response);

    return $response;
  }

  /**
   * Get a new unused PID.
   *
   * @param string $namespace
   *   The namespace to get the PID in. This defaults to default namespace of
   *   the repository. This should not contain the PID seperator, for example
   *   it should be islandora not islandora:.
   * @param int $numpids
   *   The number of pids being requested.
   *
   * @throws RepositoryException
   *
   * @return array/string
   *   If one pid is requested it is returned as a string. If multiple pids are
   *   requested they they are returned in an array containg strings.
   *   @code
   *   Array
   *   (
   *       [0] => test:7
   *       [1] => test:8
   *   )
   *   @endcode
   *
   */
  public function getNextPid($namespace = NULL, $numpids = NULL) {
    $request = "/nextPID";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'format', 'xml');
    $this->connection->addParam($request, $seperator, 'namespace', $namespace);
    $this->connection->addParam($request, $seperator, 'numPIDs', $numpids);

    $response = $this->connection->postRequest($request, 'string', '');
    $response = $this->serializer->getNextPid($response);
    return $response;
  }

  /**
   * Get the Fedora Objects XML (Foxml)
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containing the objects foxml
   *
   * @see FedoraApiM::export
   */
  public function getObjectXml($pid) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/objectXML";
    $response = $this->connection->getRequest($request);
    $response = $this->connection->serializer($response);
    return $response;
  }

  /**
   * Query relationships for a particular fedora object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $relationship
   *   (Optional) An array defining the relationship:
   *   - subject: subject of the relationship(s). Either a URI for the object
   *     or one of its datastreams. defaults to the URI of the object.
   *   - predicate: predicate of the relationship(s), if missing returns all
   *     predicates.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An indexed array with all the relationships.
   *   @code
   *   Array
   *   (
   *       [0] => Array
   *           (
   *               [subject] => islandora:strict_pdf
   *               [predicate] => Array
   *                   (
   *                       [predicate] => hasModel
   *                       [uri] => info:fedora/fedora-system:def/model#
   *                       [alias] =>
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => FALSE
   *                       [value] => fedora-system:FedoraObject-3.0
   *                   )
   *
   *           )
   *
   *       [1] => Array
   *           (
   *               [subject] => islandora:strict_pdf
   *               [predicate] => Array
   *                   (
   *                       [predicate] => bar
   *                       [uri] => http://woot/foo#
   *                       [alias] =>
   *                   )
   *
   *               [object] => Array
   *                   (
   *                       [literal] => TRUE
   *                       [value] => thedude
   *                   )
   *
   *           )
   *
   *   )
   *   @endcode
   */
  public function getRelationships($pid, $relationship = array()) {
    $pid = urlencode($pid);

    $request = "/objects/$pid/relationships";
    $seperator = "?";

    $this->connection->addParam($request, $seperator, 'format', 'xml');
    $this->connection->addParamArray($request, $seperator, $relationship, 'subject');
    $this->connection->addParamArray($request, $seperator, $relationship, 'predicate');

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->getRelationships($response);
    return $response;
  }

  /**
   * Create a new object in Fedora. This could be ingesting a XML file as a
   * string or a file. Executing this request with no XML file content will
   * result in the creation of a new, empty object (with either the specified
   * PID or a system-assigned PID). The new object will contain only a minimal
   * DC datastream specifying the dc:identifier of the object.
   *
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - pid: persistent identifier of the object to be created. If this is not
   *     supplied then either a new PID will be created for this object or the
   *     PID to be used is encoded in the XML included as the body of the
   *     request
   *   - string: The XML file defining the new object as a string
   *   - file: The XML file defining the new object as a string containing the
   *     full path to the XML file. This must not be used with the string
   *     parameter
   *   - label: the label of the new object
   *   - format: the XML format of the object to be ingested. One of
   *     info:fedora/fedora-system:FOXML-1.1,
   *     info:fedora/fedora-system:FOXML-1.0,
   *     info:fedora/fedora-system:METSFedoraExt-1.1,
   *     info:fedora/fedora-system:METSFedoraExt-1.0,
   *     info:fedora/fedora-system:ATOM-1.1,
   *     info:fedora/fedora-system:ATOMZip-1.1
   *   - encoding: 	the encoding of the XML to be ingested.  If this is
   *     specified, and given as anything other than UTF-8, you must ensure
   *     that the same encoding is declared in the XML.
   *   - namespace: The namespace to be used to create a PID for a new empty
   *     object: if a 'string' parameter is included with the request, the
   *     namespace parameter is ignored.
   *   - ownerId: the id of the user to be listed at the object owner.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return string
   *   The PID of the newly created object.
   *
   * @todo This function is a problem in Fedora < 3.5 where ownerId does not
   *   properly get set. https://jira.duraspace.org/browse/FCREPO-963. We should
   *   deal with this.
   */
  public function ingest($params = array()) {
    $request = "/objects/";
    $seperator = '?';

    if (isset($params['pid'])) {
      $pid = urlencode($params['pid']);
      $request .= "$pid";
    }
    else {
      $request .= "new";
    }

    if (isset($params['string'])) {
      $type = 'string';
      $data = $params['string'];
      $content_type = 'text/xml';
    }
    elseif (isset($params['file'])) {
      $type = 'file';
      $data = $params['file'];
      $content_type = 'text/xml';
    }
    else {
      $type = 'none';
      $data = NULL;
      $content_type = NULL;
    }

    $this->connection->addParamArray($request, $seperator, $params, 'label');
    $this->connection->addParamArray($request, $seperator, $params, 'format');
    $this->connection->addParamArray($request, $seperator, $params, 'encoding');
    $this->connection->addParamArray($request, $seperator, $params, 'namespace');
    $this->connection->addParamArray($request, $seperator, $params, 'ownerId');
    $this->connection->addParamArray($request, $seperator, $params, 'logMessage');

    $response = $this->connection->postRequest($request, $type, $data, $content_type);
    $response = $this->serializer->ingest($response);
    return $response;
  }

  /**
   * Update a datastream. Either changing its metadata, updaing the datastream
   * contents or both.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - dsFile: String containing the full path to a file that will be used
   *     as the new contents of the datastream.
   *   - dsString: String containing the new contents of the datastream.
   *   - dsLocation: String containing a URL to fetch the new datastream from.
   *     Only ONE of dsFile, dsString or dsLocation should be used.
   *   - altIDs: 	alternate identifiers for the datastream. This is a space
   *     seperated string of alternate identifiers for the datastream.
   *   - dsLabel: 	the label for the datastream.
   *   - versionable: enable versioning of the datastream.
   *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted)
   *   - formatURI: the format URI of the datastream
   *   - checksumType: the algorithm used to compute the checksum. This has to
   *     be one of: DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-384, SHA-512.
   *     If this parameter is given and no checksum is given the checksum will
   *     be computed.
   *   - checksum: 	the value of the checksum represented as a hexadecimal
   *     string. This checksum must be computed by the algorithm defined above.
   *   - mimeType: 	the MIME type of the content being added, this overrides
   *     the Content-Type request header.
   *   - logMessage: a message describing the activity being performed
   *   - lastModifiedDate: 	date/time of the last (known) modification to the
   *     datastream, if the actual last modified date is later, a 409 response
   *     is returned. This can be used for opportunistic object locking.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array contianing information about the updated datastream. This array
   *   is the same as the array returned by getDatastream.
   *
   * @see FedoraApiM::getDatastream
   */
  public function modifyDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);

    $request = "/objects/{$pid}/datastreams/{$dsid}";
    $seperator = '?';

    // Setup the file.
    if (isset($params['dsFile'])) {
      $type = 'file';
      $data = $params['dsFile'];
    }
    elseif (isset($params['dsString'])) {
      $type = 'string';
      $data = $params['dsString'];
    }
    elseif (isset($params['dsLocation'])) {
      $type = 'none';
      $data = NULL;
      $this->connection->addParamArray($request, $seperator, $params, 'dsLocation');
    }
    else {
      $type = 'none';
      $data = NULL;
    }

    $this->connection->addParamArray($request, $seperator, $params, 'altIDs');
    $this->connection->addParamArray($request, $seperator, $params, 'dsLabel');
    $this->connection->addParamArray($request, $seperator, $params, 'versionable');
    $this->connection->addParamArray($request, $seperator, $params, 'dsState');
    $this->connection->addParamArray($request, $seperator, $params, 'formatURI');
    $this->connection->addParamArray($request, $seperator, $params, 'checksumType');
    $this->connection->addParamArray($request, $seperator, $params, 'mimeType');
    $this->connection->addParamArray($request, $seperator, $params, 'logMessage');
    $this->connection->addParamArray($request, $seperator, $params, 'lastModifiedDate');

    $response = $this->connection->putRequest($request, $type, $data);
    $response = $this->serializer->modifyDatastream($response);

    return $response;
  }

  /**
   * Update Fedora Object parameters.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - label: object label.
   *   - ownerId: the id of the user to be listed at the object owner.
   *   - state: the new object state - *A*ctive, *I*nactive, or *D*eleted.
   *   - logMessage: a message describing the activity being performed.
   *   - lastModifiedDate: date/time of the last (known) modification to the
   *     datastream, if the actual last modified date is later, a 409 response
   *     is returned. This can be used for opportunistic object locking.
   *
   * @throws RepositoryException
   *
   * @return string
   *   A string containg the timestamp of the object modification.
   */
  public function modifyObject($pid, $params = NULL) {
    $pid = urlencode($pid);
    $request = "/objects/$pid";
    $seperator = '?';

    $this->connection->addParamArray($request, $seperator, $params, 'label');
    $this->connection->addParamArray($request, $seperator, $params, 'ownerId');
    $this->connection->addParamArray($request, $seperator, $params, 'state');
    $this->connection->addParamArray($request, $seperator, $params, 'logMessage');
    $this->connection->addParamArray($request, $seperator, $params, 'lastModifiedDate');

    $response = $this->connection->putRequest($request);
    $response = $this->serializer->modifyObject($response);
    return $response;
  }

  /**
   * Purge a datastream from from Fedora. This perminatly removes the
   * datastream and all its associated data.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier
   * @param array $params
   *   (optional) An array that can have one or more of the following elements:
   *   - startDT: the (inclusive) start date-time stamp of the range. If not
   *     specified, this is taken to be the lowest possible value, and thus,
   *     the entire version history up to the endDT will be purged.
   *   - endDT: the (inclusive) ending date-time stamp of the range. If not
   *     specified, this is taken to be the greatest possible value, and thus,
   *     the entire version history back to the startDT will be purged.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array containing the timestamps of the datastreams that were removed.
   *   @code
   *   Array
   *   (
   *       [0] => 2012-03-08T18:44:15.214Z
   *       [1] => 2012-03-08T18:44:15.336Z
   *   )
   *   @endcode
   */
  public function purgeDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/objects/$pid/datastreams/$dsid";
    $seperator = '?';

    $this->connection->addParamArray($request, $seperator, $params, 'startDT');
    $this->connection->addParamArray($request, $seperator, $params, 'endDT');
    $this->connection->addParamArray($request, $seperator, $params, 'logMessage');

    $response = $this->connection->deleteRequest($request);
    $response = $this->serializer->purgeDatastream($response);
    return $response;
  }

  /**
   * Purge an object.
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $log_message
   *   (optional)  A message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return string
   *   Timestamp when object was deleted.
   */
  public function purgeObject($pid, $log_message = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'logMessage', $log_message);
    $response = $this->connection->deleteRequest($request);
    //$response = $this->serializer->purgeObject($response);
    return $response;
  }

  /**
   * Validate an object.
   *
   * @param string $pid
   *    Persistent identifier of the digital object.
   * @param array $as_of_date_time
   *   (optional) Indicates that the result should be relative to the
   *     digital object as it existed at the given date and time. Defaults to
   *     the most recent version.
   *
   * @throws RepositoryException
   *
   * @return array
   *   An array containing the validation results.
   *   @code
   *   Array
   *   (
   *       [valid] => false
   *       [contentModels] => Array
   *           (
   *               [0] => "info:fedora/fedora-system:FedoraObject-3.0"
   *           )
   *       [problems] => Array
   *           (
   *               [0] => "Problem description"
   *           )
   *       [datastreamProblems] => Array
   *           (
   *               [dsid] => Array
   *               (
   *                   [0] => "Problem description"
   *               )
   *           )
   *   )
   *   @endcode
   */
  public function validate($pid, $as_of_date_time = NULL) {
    $pid = urlencode($pid);

    $request = "/objects/{$pid}/validate";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'asOfDateTime', $as_of_date_time);

    $response = $this->connection->getRequest($request);
    $response = $this->serializer->validate($response);
    return $response;
  }

  public function upload($file) {
    $request = "/upload";
    $response = $this->connection->postRequest($request, 'file', $file);
    $response = $this->serializer->upload($response);
    return $response;
  }

  public function registerNamespace($prefix, $uri) {
    $prefix = urlencode($prefix);

    $request = "/namespaces/$prefix";

    try {
      $response = $this->connection->postRequest($request, 'string', $uri);
    } catch (RepositoryException $e) {
      // error
      return NULL;
    }
    return $response;
  }

  public function getRegisteredNamespaces() {
    $request = "/namespaces";

    $response = $this->connection->getRequest($request, false, NULL, "application/json");
    return $response['content'];
  }

}
