<?php

/**
 * @file
 * This file provides some methods for doing RDF queries.
 *
 * The essance of this file was taken from some commits that Adam Vessy made to
 * Islandora 6.x, so I'd like to give him some credit here.
 */
class RepositoryQuery {

  public $connection;

  /**
   * Construct a new RI object.
   *
   * @param RepositoryConnection $connection
   *   The connection to connect to the RI with.
   */
  public function __construct(RepositoryConnection $connection) {
    $this->connection = $connection;
  }

  /**
   * Parse the passed in Sparql XML string into a more easily usable format.
   *
   * @param string $sparql
   *   A string containing Sparql result XML.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  public static function parseSparqlResults($sparql) {
    // Load the results into a SimpleXMLElement.
    $doc = new SimpleXMLElement($sparql, 0, FALSE, 'http://www.w3.org/2001/sw/DataAccess/rf1/result');

    // Storage.
    $results = array();
    // Build the results.
    foreach ($doc->results->children() as $result) {
      // Built a single result.
      $r = array();
      foreach ($result->children() as $element) {
        $val = array();

        $attrs = $element->attributes();
        if (!empty($attrs['uri'])) {
          $val['value'] = self::pidUriToBarePid((string) $attrs['uri']);
          $val['uri'] = (string) $attrs['uri'];
          $val['type'] = 'pid';
        }
        else {
          $val['type'] = 'literal';
          $val['value'] = (string) $element;
        }

        // Map the name to the value in the array.
        $r[$element->getName()] = $val;
      }

      // Add the single result to the set to return.
      $results[] = $r;
    }
    return $results;
  }

  /**
   * Performs the given Resource Index query and return the results.
   *
   * @param string $query
   *   A string containing the RI query to perform.
   * @param string $type
   *   The type of query to perform, as used by the risearch interface.
   * @param int $limit
   *   An integer, used to limit the number of results to return.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  function query($query, $type = 'itql', $limit = -1) {
    // Construct the query URL.
    if ($type == 'SQL2') {
      return $this->sql2Query($query, $limit);
    }
    $url = '/risearch';
    $seperator = '?';

    $this->connection->addParam($url, $seperator, 'type', 'tuples');
    $this->connection->addParam($url, $seperator, 'flush', TRUE);
    $this->connection->addParam($url, $seperator, 'format', 'Sparql');
    $this->connection->addParam($url, $seperator, 'lang', $type);
    $this->connection->addParam($url, $seperator, 'query', $query);

    // Add limit if provided.
    if ($limit > 0) {
      $this->connection->addParam($url, $seperator, 'limit', $limit);
    }

    $result = $this->connection->getRequest($url);

    // Pass the query's results off to a decent parser.
    return self::parseSparqlResults($result['content']);
  }

  /**
   * 
   * @param string $query
   *   a SQL2 query string
   * @param int $limit
   *   limit to this int, not currently implemented
   * @return array
   * @throws Exception
   */
  function sql2Query($query, $limit) {
    //TODO: fix this as we are making some assumptions here
    $url = '/modeshape/repo/fedora/query';
    $result = $this->connection->postRequest($url, 'string', $query, 'application/jcr+sql2');
    if ($result['status'] == '200') {
      return $this->parseSql2results($result);
    }
    else
      throw new Exception(t('Error running sql2 query %query', array('%query' => $query)));
  }

  /**
   * SQL2 is only available on FCREPO4 and higher
   * This implementation is currently a hack for integration with FCREPO4, which
   * at the time this was written didn't have a concept of relationships etc.
   * 
   * We also need to figure out a way to map jcr properties to names we expect
   * in the theme layer as it assumes the array will have an object and title keys.
   *
   * @param type $sql2Results
   *   the results of an SQL2 query 
   * @return string
   */
  function parseSql2Results($sql2Results) {
    $result_object = json_decode($sql2Results['content']);
    $results = array();
    // Build the results.
    foreach ($result_object->rows as $result) {
      // Built a single result.
      $r = array();

      $val = array();      
        $column = 'jcr:name';
        $val['value'] = $result->$column;
        $val['uri'] = $result->$column;
        $val['type'] = 'pid';    
      

      // Map the name to the value in the array.
      $r['object'] = $val;
      $r['title']  = array('type' => 'literal','value'=> $result->$column);
      $r['content']  = array('type' => 'literal','value'=>'this should be the cmodel');
      // Add the single result to the set to return.
      $results[] = $r;
    }
    return $results;
  }

  /**
   * Thin wrapper for self::_performRiQuery().
   *
   * @see self::performRiQuery()
   */
  public function itqlQuery($query, $limit = -1) {
    return $this->query($query, 'itql', $limit);
  }

  /**
   * Thin wrapper for self::performRiQuery().
   *
   * @see self::_performRiQuery()
   */
  public function sparqlQuery($query, $limit = -1, $offset = 0) {
    return $this->query($query, 'sparql', $limit, $offset);
  }

  /**
   * Utility function used in self::performRiQuery().
   *
   * Strips off the 'info:fedora/' prefix from the passed in string.
   *
   * @param string $uri
   *   A string containing a URI.
   *
   * @return string
   *   The input string less the 'info:fedora/' prefix (if it has it).
   *   The original string otherwise.
   */
  protected static function pidUriToBarePid($uri) {
    $chunk = 'info:fedora/';
    $pos = strpos($uri, $chunk);
    // Remove info:fedora/ chunk.
    if ($pos === 0) {
      return substr($uri, strlen($chunk));
    }
    // Doesn't start with info:fedora/ chunk...
    else {
      return $uri;
    }
  }

}
