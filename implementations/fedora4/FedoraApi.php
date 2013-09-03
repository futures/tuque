<?php

/**
 * @file
 * The RAW API wrappers for the Fedora interface.
 *
 * This file currently contains fairly raw wrappers around the Fedora REST
 * interface. These could also be reinmplemented to use for example the Fedora
 * SOAP interface. If there are version specific modifications to be made for
 * Fedora, this is the place to make them.
 */
set_include_path("sites/all/libraries/tuque/");
require_once 'RepositoryException.php';
require_once 'implementations/fedora3/FedoraApi.php';
require_once 'implementations/fedora3/RepositoryConnection.php';

/**
 * This is a simple class that brings FedoraApiM and FedoraApiA together.
 */
class Fedora4Api extends FedoraApi {

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

  /**
   *
   */
  public $connection;

  /**
   * Constructor for the FedoraApi object.
   *
   * @param RepositoryConnection $connection
   *   (Optional) If one isn't provided a default one will be used.
   * @param FedoraApiSerializer $serializer
   *   (Optional) If one isn't provided a default will be used.
   */
  public function __construct(RepositoryConnection $connection = NULL, Fedora4ApiSerializer $serializer = NULL) {
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

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiA extends FedoraApiA {

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
  public function __construct(RepositoryConnection $connection, Fedora4ApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }

  /**
   * Does a simple call just to check if the repository is accessible.
   *
   * @return array
   *   Stubbed values for now.
   */
  public function describeRepository() {
    $options['headers'] = array('Accept: application/rdf+json');
    // Do the call just to test the connection nothing else.
    $response = $this->connection->getRequest("/", $options);
    return array(
      // Version number doesn't come down via any requests as of now.
      'repositoryVersion' => '4.0.0',
    );
  }

  /**
   * Not implemented.
   */
  public function findObjects($type, $query, $max_results = NULL, $display_fields = array('pid', 'title')) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Not implemented.
   */
  public function resumeFindObjects($session_token) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Gets the given datastream as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function getDatastreamDissemination($pid, $dsid, $as_of_date_time = NULL, $file = NULL, $param = array()) {
    $request = "/$pid/$dsid/fcr:content";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $response = $this->connection->getRequest($request, array('file' => $file));
    $response = $this->serializer->getDatastreamDissemination($response, $file);
    return $response;
  }

  /**
   * Not implemented.
   */
  public function getDissemination($pid, $sdef_pid, $method, $method_parameters = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Not implemented.
   */
  public function getObjectHistory($pid) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * Gets the given object as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function getObjectProfile($pid, $as_of_date_time = NULL, $params = array()) {
    ;
    $request = "/{$pid}";

    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $object = $this->serializer->getNode($id, $response['content']);
    $return_array = array('objState' => 'A');
    $return_array['objLabel'] = isset($object['http://purl.org/dc/terms/title']) ? $object['http://purl.org/dc/terms/title'] : 'Defualt Label';
    $return_array['objOwnerId'] = isset($object['createdBy']) ? $object['createdBy'] : '<unknown>';
    //read the relashinship 
    $relationship = array();
    foreach ($object as $key => $values) {
      if (preg_match('.rels-ext.', $key)) {
        $predicate = substr($key, strpos($key, 'rels-ext#') + strlen('rels-ext#'));
        if (is_array($values) & strcasecmp($predicate, 'hasModel')) {
          foreach ($values as $value) {
            $relationship[] = array($value);
          }
        } else if ($values & strcasecmp($predicate, 'hasModel')) {
          $relationship[] = array($values);
        }
      }
    }
    $return_array['objModels'] = $relationship;
    $return_array['objLastModDate']=isset($object['lastModified'])?$object['lastModified']:'1900-01-01T00:00:00.000Z';
    $return_array['objCreateDate'] = isset($object['created'])?$object['created']:'1900-01-01T00:00:00.000Z';
    return $return_array;
  }

  /**
   * Gets the given object as of the given date time.
   *
   * Parameter $as_of_date_time no longer has any affect at the moment.
   * We will reintroduce it by doing two requests on to fcr:versions to get
   * the version ID and then another to getVersions for now just get the
   * latest.
   */
  public function listDatastreams($pid, $as_of_date_time = NULL, $params = array()) {
    $request = "/{$pid}";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $object = $this->serializer->getNode($id, $response['content']);
    $out = array();
    if (isset($object['http://fedora.info/definitions/v4/repository#hasChild'])) {
      $apim = new Fedora4ApiM($this->connection, $this->serializer);
      if (is_array($object['http://fedora.info/definitions/v4/repository#hasChild'])) {
        foreach ($object['http://fedora.info/definitions/v4/repository#hasChild'] as $ds) {
          $url = $this->connection->buildUrl($request);
          $length = strlen($url) + 1;
          $dsid = substr($ds, $length);

          $datastream = $apim->getDatastream($pid, $dsid);

          $out[$dsid] = array(
            'label' => $datastream['dsLabel'],
            'mimetype' => $datastream['dsMIME'],
          );
        }
      } else {
        $ds = $object['http://fedora.info/definitions/v4/repository#hasChild'];
        $url = $this->connection->buildUrl($request);
        $length = strlen($url) + 1;
        $dsid = substr($ds, $length);

        $datastream = $apim->getDatastream($pid, $dsid, $params);

        $out[$dsid] = array(
          'label' => $datastream['dsLabel'],
          'mimetype' => $datastream['dsMIME'],
        );
      }
    }
    return $out;
  }

  /**
   * Not implemented.
   */
  public function listMethods($pid, $sdef_pid = '', $as_of_date_time = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

}

/**
 * This class implements the Fedora API-M interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class Fedora4ApiM extends FedoraApiM {

  /**
   * Constructor for the new FedoraApiM object.
   *
   * @param RepositoryConnection $connection
   *   Takes the Respository Connection object for the Respository this API
   *   should connect to.
   * @param FedoraApiSerializer $serializer
   *   Takes the serializer object to that will be used to serialze the XML
   *   Fedora returns.
   */
  public function __construct(RepositoryConnection $connection, Fedora4ApiSerializer $serializer) {
    $this->connection = $connection;
    $this->serializer = $serializer;
  }
  
  /**
   * generate a defult dc datastream for a newly ingested object
   * 
   * @param String $pid
   *   The pid of object that should be generated
   * @param String $params
   *   current params:
   *   'label' object label,
   *   'txid' transaction id (if need)
   * @return 
   *   The dc datastream
   */
  public function generateDC($pid, $params = array()) {
    $title = null;
    if (isset($params['label'])) {
      $title = $params['label'];
    }
    //add prorperties
    $url = $this->connection->buildUrl("/$pid");
    $query = "PREFIX dc: <http://purl.org/dc/terms/> \n
        INSERT {<$url> dc:identifier \"$pid\"";
    if ($title) {
      $query .= ";dc:title \"$title\"";
    }
    $query.= "}\nWHERE {}\n";
    $options = array();
    $request = "/$pid";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $result = $this->connection->postRequest($request, 'string', $query, 'application/sparql-update', $options);
    $pidlenth = strlen($pid);
    $deletelenth = $pidlenth + 4;
    $purgeurl = substr($result['content'], 0, $deletelenth);
    $result = $this->purgeObject($purgeurl, NULL, $params);
    //get propeties and get dc
    $dcxml = "<oai_dc:dc xmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\" 
      xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" 
      xsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/ 
      http://www.openarchives.org/OAI/2.0/oai_dc.xsd\">";
    if ($title) {
      $dcxml.="<dc:title>$title</dc:title>\n";
    }
    $dcxml.="<dc:identifier>$pid</dc:identifier>\n</oai_dc:dc>";
    $response = $this->addDatastream($pid, 'DC', 'string', $dcxml, $params);
    return $this->getDatastream($pid, 'DC', $params);
  }

  /**
   * Add a datastream.
   *
   * Parameters that are no longer supported and will cause problems.
   * $type: When it equals 'url'
   * $params: 'dsLocation', 'controlGroup', 'altIDs', 'dsLabel', 'versionable',
   *          'dsState', 'formatURI', 'checksumType', 'checksum', 'mimeType',
   *          'logMessage'
   */
  public function addDatastream($pid, $dsid, $type, $file, $params) {
    $request = "/$pid/$dsid";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $seperator = '?';
    switch (strtolower($type)) {
      case 'file':
      case 'string':
        break;
      default:
        throw new RepositoryBadArguementException("Type must be one of: file, string. ($type)");
        break;
    }
    $this->connection->addParam($request, $seperator, 'mixin', 'fedora:datastream');
    $response = $this->connection->postRequest($request, $type, $file, 'text/html', $params);
    return $this->getDatastream($pid, $dsid, $params);
  }

  /**
   * Add relation ship to object. The relation ship should be add under the 
   * namespace 'fedorarelsext'
   *
   * @param String $pid
   *   The pid of object that should be generated
   * 
   * @param Array $relationship
   *  The relationship that should be added
   *  Array(
   *    'predicate'=> predicate,
   *    'object'=> object
   *  )
   * 
   * @param Boolean is_literal
   * 
   * @param String datatype 
   * @return 
   *   All relationships belong to the object
   */
  public function addRelationship($pid, $relationship, $is_literal = false, $datatype = NULL) {
    if (!isset($relationship['predicate'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a predicate element');
    }
    if (!isset($relationship['object'])) {
      throw new RepositoryBadArguementException('Relationship array must contain a object element');
    }
    $request = "/$pid";
    $url = $this->connection->buildUrl($request);
    $predicate = $relationship['predicate'];
    $object = $relationship['object'];
    $query = "PREFIX fedorarelsext: <http://fedora.info/definitions/v4/rels-ext#>\n
      INSERT {<$url> fedorarelsext:$predicate \"info:fedora/$object\"} WHERE {}";
    $options = array();
    $result = $this->connection->postRequest($request, 'string', $query, 'application/sparql-update', $options);
    $query = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>\n
      INSERT {<$url> rdf:resource \"info:fedora/$object\"} WHERE {}";
    $result = $this->connection->postRequest($request, 'string', $query, 'application/sparql-update', $options);
    return $this->getRelationships($pid);
  }

  /**
   * Not implemented.
   *
   * No support for FOXML at the moment.
   */
  public function export($pid, $params = array()) {
    $request = "/{$pid}/fcr:export";
    $seperator = '?';
    if (isset($params['exportFormat'])) {
      $this->connection->addParamArray($request, $seperator, $params['exportFormat'], 'fornat');
    }
    $response = $this->connection->getRequest($request);
    $return = $this->serializer->export($response);
    return $return;
  }

  /**
   * See add Datastream for what is stubbed etc.
   */
  public function getDatastream($pid, $dsid, $params = array()) {

    $request = "/{$pid}/$dsid";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $options['headers_only'] = TRUE;
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $responseArray = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $id = str_replace('%3A', ':', $id);
    $ds = $this->serializer->getNode($id, $responseArray);

    $return_array = array(
      // Datastream Labels are not implemented yet.
      'dsLabel' => 'Default Label',
      // Versioning doesn't work currently.
      'dsVersionID' => "$dsid.0",
      //'dsCreateDate' => $ds['http://fedora.info/definitions/v4/repository#created'],
      // Datastream state not implemented yet.
      'dsState' => 'A',
      // Format URI is no longer supported.
      'dsFormatURI' => '',
      // Control Group is no longer supported.
      'dsControlGroup' => 'M',
      //'dsSize' => $ds['content']['size'],
      // Versioning doesn't work currently.
      'dsVersionable' => false,
      // dsInfoType, dsLocationType, and dsLocation are not relevent at the
      // moment.
      'dsInfoType' => '',
      'dsLocation' => '',
      'dsLocationType' => '',
      // Not provied by the request just assuming the call had the correct
      // values for now.
//      'dsChecksumType' => $params['checksumType'],
//      'dsChecksum' => $params['checksum'],
      // No message system for versions at the moment.
      'dsLogMessage' => '',
    );
    if (isset($ds['http://fedora.info/definitions/v4/repository#created'])) {
      $return_array['dsCreateDate'] = $ds['http://fedora.info/definitions/v4/repository#created'];
    }
    if (isset($ds['http://fedora.info/definitions/v4/rest-api#mimeType'])) {
      $return_array['dsMIME'] = $ds['http://fedora.info/definitions/v4/rest-api#mimeType'];
    } else {
      $return_array['dsMIME'] = 'application/rdf+xml';
    }
    return $return_array;
  }

  /**
   * Versioning is borken at the moment.
   */
  public function getDatastreamHistory($pid, $dsid) {
    return array(
      $this->getDataststream($pid, $dsid)
    );
  }

  /**
   * Significantly different than before, since PIDS are now hierarchical.
   *
   * Added $pid as the last parameter, but made it a requirement so I could
   * catch the locations in which it was being used.
   *
   * Namespaces are no longer supported, perhaps we can use workspaces going
   * forward.
   */
  public function getNextPid($namespace = NULL, $numpids = NULL, $pid = NULL) {
    $request = isset($pid) ? "/$pid/fcr:pid" : '/fcr:pid';
    $seperator = '?';
    $id = $this->connection->buildUrl($request);
    $this->connection->addParam($request, $seperator, 'numPids', $numpids);
    $options = array(
      'headers' => array('Accept: application/rdf+json'),
    );
    $response = $this->connection->postRequest($request, 'none', NULL, NULL, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $new_pids = $response['content'][$id]['info:fedora/fedora-system:def/internal#hasMember'];
    foreach ($new_pids as &$pid) {
      $pid = preg_replace('/^.*\/rest\/(.*)$/', '$1', $pid['value']);
    }
    return count($new_pids) == 1 ? $new_pids[0] : $new_pids;
  }

  /**
   * FOXML is not implemented yet.
   */
  public function getObjectXml($pid) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("TODO implement.", E_USER_NOTICE);
  }

  /**
   * Get relationships of an object
   * 
   *  @param String $pid
   *   The pid of object that should be generated 
   * 
   *  @return 
   *   The relationships which was listed under the fedorarelext 
   */
  public function getRelationships($pid, $relationship = array()) {
    $request = "/{$pid}";
    $options['headers'] = array('Accept: application/rdf+json');
    $response = $this->connection->getRequest($request, $options);
    $response['content'] = json_decode($response['content'], TRUE);
    $id = $this->connection->buildUrl($request);
    $object = $this->serializer->getNode($id, $response['content']);
    $relationship = array();
    foreach ($object as $key => $values) {
      if (preg_match('.rels-ext.', $key)) {
        $predicate = substr($key, strpos($key, 'rels-ext#') + strlen('rels-ext#'));
        if (is_array($values)) {
          foreach ($values as $value) {
            $relationship[] = array($predicate => $value);
          }
        } else if ($values) {
          $relationship[] = array($predicate => $values);
        }
      }
    }
    return $relationship;
  }

  /**
   * We should make this work with transactions.
   *
   * Parameter problems:
   *   - label: is ignored.
   *   - format: is ignored, assumed to be info:fedora/fedora-system:FOXML-1.1.
   *   - encoding: is ignored.
   *   - namespace: is ignored, namespaces are no longer relevent.
   *   - ownerId: also ignored until we implement some sorta filter.
   *   - logMessage: is ignored.
   */
  public function ingest($params = array()) {
    // Process the parameters
    if (isset($params['string'])) {
      $foxml = new SimpleXMLElement($params['string']);
    } elseif (isset($params['file'])) {
      $foxml = simplexml_load_file($params['file']);
    }
    $datastreams = array();
    if (isset($foxml)) {
      $pid = (string) $foxml['PID'];
      $children = $foxml->children('foxml', TRUE);
      $attribute = function(SimpleXMLElement $el, $attr) {
            $results = $el->xpath("@{$attr}");
            return (string) array_shift($results);
          };
      foreach ($children->datastream as $datastream) {
        $dsid = $attribute($datastream, 'ID');
// Just grab the first version for now.
        $version = $datastream->datastreamVersion[0];
        $type = 'none';
        $data = NULL;
        if (isset($version->contentLocation)) {
          $type = 'file';
          $data = $attribute($version->contentLocation, 'REF');
        } elseif (isset($version->xmlContent)) {
          $type = 'string';
          $children = $version->xmlContent->xpath('*');
          $data = (string) array_pop($children)->asXML();
        } elseif (isset($version->binaryContent)) {
          $type = 'string';
          $data = (string) $version->binaryContent;
        }
        $datastreams[$dsid] = array(
          'mimeType' => $attribute($version, 'MIMETYPE'),
          'type' => $type,
          'data' => $data,
        );
      }
    }
    if (empty($pid)) {
      $pid = isset($params['pid']) ? $params['pid'] : '';
    }
// Create the object.
    $request = empty($pid) ? "/fcr:new" : "/$pid";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $response = $this->connection->postRequest($request);
    $pid = $response['content'];
    $pid = substr($pid, 1);
    if (isset($params['label'])) {
      $label = $params['label'];
    } else {
      $label = NULL;
    }
    foreach ($datastreams as $dsid => $ds) {
      $ds_params = array(
        'mimeType' => $ds['mimeType'],
      );
      if (isset($params['txID'])) {
        $ds_params['txID'] = $params['txID'];
      }
      $this->addDatastream($pid, $dsid, $ds['type'], $ds['data'], $ds_params);
    }
    try {
      $dc = $this->getDatastream($pid, 'DC');
    } catch (Exception $e) {
      $dc_params = array('label' => $label);
      if (isset($params['txID'])) {
        $dc_params['txID'] = $params['txID'];
      }
      $dc = $this->generateDC($pid, $dc_params);
    }
    return $pid;
  }

  /**
   * 
   * @return transaction ID
   */
  public function addTransaction() {
    //post transactions
    $request = "/fcr:tx";
    $result = $this->connection->postRequest($request, 'none');
    //get transaction ID
    $headers = $result['headers'];
    $txstart = strpos($headers, 'tx:') + 3;
    $txId = substr($headers, $txstart, 36);
    return $txId;
  }

  public function commitTransaction($transactionID) {
    $request = "/tx:{$transactionID}/fcr:tx/fcr:commit";
    $this->connection->postRequest($request, 'none', NULL, 'text/plain');
  }

  public function rollbackTransaction($transactionID) {
    $request = "/tx:{$transactionID}/fcr:tx/fcr:rollback";
    $this->connection->postRequest($request, 'none', NULL, 'text/plain');
  }

  public function registerNamespace($prefix, $uri) {
    $query = "INSERT {<$uri> <http://purl.org/vocab/vann/preferredNamespacePrefix>
      \"$prefix\"} WHERE {}";
    $result = $this->connection->postRequest($request, 'string', $query, 'application/sparql-update', $options);
    if ($result['status'] == '204') {
      return $prefix;
    } else {
      return null;
    }
  }

  /**
   * Properties aside from content are just ignored for the moment.
   *
   * No longer sends up the lastModifiedDate, so we don't not if the content
   * has changed since we last retrieved it.
   */
  public function modifyDatastream($pid, $dsid, $params = array()) {
    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/{$pid}/{$dsid}";
    $seperator = '?';
    $options = array(
      'headers' => array("Content-Type: {$params['mimeType']}"),
    );
    // Setup the file.
    if (isset($params['dsFile'])) {
      $type = 'file';
      $data = $params['dsFile'];
    } elseif (isset($params['dsString'])) {
      $type = 'string';
      $data = $params['dsString'];
    } elseif (isset($params['dsLocation'])) {
      throw new RepositoryBadArguementException("dsLocation is no longer supported");
    } else {
      $type = 'none';
      $data = NULL;
    }
    $response = $this->connection->putRequest($request, $type, $data, $options);
    return $this->getDatastream($pid, $dsid);
  }

  /**
   * Not implemented.
   */
  public function modifyObject($pid, $params = NULL) {
    trigger_error("TODO implement.", E_USER_NOTICE);
  }

  /**
   * No longer returns the timestamps of the purged datastreams.
   */
  public function purgeDatastream($pid, $dsid, $params = array()) {

    $pid = urlencode($pid);
    $dsid = urlencode($dsid);
    $request = "/{$pid}/{$dsid}";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $this->connection->deleteRequest($request);
    // @todo The returned timestamps don't seem to ever get used, so we'll
    // ignore them for now.
  }

  /**
   * No longer returns the timestamps of the purged object.
   */
  public function purgeObject($pid, $log_message = NULL, $params = array()) {
    $pid = urlencode($pid);
    $request = "/{$pid}";
    if (isset($params['txID'])) {
      $request = "/tx:" . $params['txID'] . $request;
    }
    $this->connection->deleteRequest($request);

    //return $result;
  }

  /**
   * Not implemented.
   */
  public function validate($pid, $as_of_date_time = NULL) {
    // Doesn't seem to be used by islandora proper.
    trigger_error("Deprecated function called.", E_USER_NOTICE);
  }

  /**
   * This is significantly different as it doesn't exist anymore.
   *
   * But to stub it we return a url to the file on disc so that we can use the
   * same code.
   */
  public function upload($file) {
    $temp = tempnam(sys_get_temp_dir(), 'tuque');
    copy($file, $temp);
    return $temp;
  }

}

