<?php

/**
 * This class implements the Fedora API-A interface. This is a light wrapper
 * around the Fedora interface. Very little attempt is put into putting things
 * into native PHP datastructures.
 *
 * See this page for more information:
 * https://wiki.duraspace.org/display/FEDORA35/REST+API
 */
class FedoraApiA {

  /**
   * returns the version of the Fedora repository.  Useful for determining what
   * functions/properties could be available
   * @staticvar type $version
   * @return string
   *   usually a number
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
   * FCREPO4 exposes an events feed
   * @return string
   *   an rss feed
   * @throws Exception
   *   if function is not supported
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

  /**
   * Query fedora to return a list of objects.
   *
   * @param string $type
   *   The type of query. Decides the format of the next parameter. Valid
   *   options are:
   *   - query: specific query on certain fields
   *   - terms: search in any field
   * @param string $query
   *   The format of this parameter depends on what was passed to type. The
   *   formats are:
   *   - query: A sequence of space-separated conditions. A condition consists
   *     of a metadata element name followed directly by an operator, followed
   *     directly by a value. Valid element names are (pid, label, state,
   *     ownerId, cDate, mDate, dcmDate, title, creator, subject, description,
   *     publisher, contributor, date, type, format, identifier, source,
   *     language, relation, coverage, rights). Valid operators are:
   *     contains (~), equals (=), greater than (>), less than (<), greater than
   *     or equals (>=), less than or equals (<=). The contains (~) operator
   *     may be used in combination with the ? and * wildcards to query for
   *     simple string patterns. Values may be any string. If the string
   *     contains a space, the value should begin and end with a single quote
   *     character ('). If all conditions are met for an object, the object is
   *     considered a match.
   *   - terms: A phrase represented as a sequence of characters (including the
   *     ? and * wildcards) for the search. If this sequence is found in any of
   *     the fields for an object, the object is considered a match.
   * @param int $max_results
   *   (optional) Default: 25. The maximum number of results that the server
   *   should provide at once.
   * @param array $display_fields
   *   (optional) Default: array('pid', 'title'). The fields to be returned as
   *   an indexed array. Valid element names are the same as the ones given for
   *   the query parameter.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   The results are returned in an array key called 'results'. If there
   *   are more results that aren't returned then the search session information
   *   is contained in a key called 'session'. Note that it is possible for
   *   some display fields to be multivalued, such as identifier (DC allows
   *   multiple DC identifier results) in the case there are multiple results
   *   an array is returned instread of a string, this indexed array contains
   *   all of the values.
   *   @code
   *   Array
   *   (
   *      [session] => Array
   *          (
   *              [token] => 96b2604f040067645f45daf029062d6e
   *              [cursor] => 0
   *              [expirationDate] => 2012-03-07T14:28:24.886Z
   *          )
   *
   *      [results] => Array
   *          (
   *              [0] => Array
   *                  (
   *                      [pid] => islandora:collectionCModel
   *                      [title] => Islandora Collection Content Model
   *                      [identifier] => Contents of DC:Identifier
   *                  )
   *
   *              [1] => Array
   *                  (
   *                      [pid] => islandora:testCModel
   *                      [title] => Test content model for Ari
   *                      [identifier] => Array
   *                          (
   *                              [0] => Contents of first DC:Identifier
   *                              [1] => Contents of seconds DC:Identifier
   *                          )
   *
   *                  )
   *
   *          )
   *
   *    )
   *    @endcode
   */
  public function findObjects($type, $query, $max_results = NULL, $display_fields = array('pid', 'title')) {
    $request = "/objects";
    $seperator = '?';

    $this->connection->addParam($request, $seperator, 'resultFormat', 'xml');

    switch ($type) {
      case 'terms':
        $this->connection->addParam($request, $seperator, 'terms', $query);
        break;

      case 'query':
        $this->connection->addParam($request, $seperator, 'query', $query);
        break;

      default:
        throw new RepositoryBadArguementException('$type must be either: terms or query.');
    }

    $this->connection->addParam($request, $seperator, 'maxResults', $max_results);

    if (is_array($display_fields)) {
      foreach ($display_fields as $display) {
        $this->connection->addParam($request, $seperator, $display, 'true');
      }
    }

    $response = $this->connection->getRequest($request);
    //$response = $this->serializer->findObjects($response);
    return $response;
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
class FedoraApiM {

  /**
   * Add a new datastream to a fedora object. The datastreams are sent to Fedora
   * using a multipart post if a string or file is provided otherwise Fedora
   * will go out and fetch the URL
   *
   * @param string $pid
   *   Persistent identifier of the digital object.
   * @param string $dsid
   *   Datastream identifier.
   * @param string $type
   *   This parameter tells the function what type of arguement is given for
   *   file. It must be one of:
   *   - string: The datastream is passed as a string.
   *   - file: The datastream is contained in a file.
   *   - url: The datastream is located at a URL, which is passed as a string.
   *     this is the only option that can be used for R and E type datastreams.
   * @param string $file
   *   This parameter depends on what is selected for $type.
   *   - string: A string containing the datastream.
   *   - file: A string containing the file name that contains the datastream.
   *     The file name must be a full path.
   *   - url: A string containing the publically accessable URL that the
   *     datastream is located at.
   * @param array() $params
   *   (optional) An array that can have one or more of the following elements:
   *   - controlGroup: one of "X", "M", "R", or "E" (Inline *X*ML, *M*anaged
   *     Content, *R*edirect, or *E*xternal Referenced). Default: X.
   *   - altIDs: alternate identifiers for the datastream. A space seperated
   *     list of alternate identifiers for the datastream.
   *   - dsLabel: the label for the datastream.
   *   - versionable: enable versioning of the datastream (boolean).
   *   - dsState: one of "A", "I", "D" (*A*ctive, *I*nactive, *D*eleted).
   *   - formatURI: the format URI of the datastream.
   *   - checksumType: the algorithm used to compute the checksum. One of
   *     DEFAULT, DISABLED, MD5, SHA-1, SHA-256, SHA-385, SHA-512.
   *   - checksum: the value of the checksum represented as a hexadecimal
   *     string.
   *   - mimeType: the MIME type of the content being added, this overrides the
   *     Content-Type request header.
   *   - logMessage: a message describing the activity being performed.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   Returns an array describing the new datastream. This is the same array
   *   returned by getDatastream. This may also contain an dsAltID key, that
   *   contains any alternate ids if any are specified.
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
   *       [dsLogMessage] =>
   *   )
   *   @endcode
   *
   * @see FedoraApiM::getDatastream
   */
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
