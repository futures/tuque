<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "RepositoryQuery.php";
require_once "FoxmlDocument.php";
require_once "Object.php";

/**
 * An abstract repository interface.
 *
 * This can be used to override the implementation of the Repository.
 */
abstract class AbstractRepository extends MagicProperty {

  /**
   * This method is a factory that will return a new repositoryobject object
   * that can be manipulated and then ingested into the repository.
   *
   * @param string $id
   *   The ID to assign to this object. There are three options:
   *   - NULL: An ID will be assigned.
   *   - A namespace: An ID will be assigned in this namespace.
   *   - A whole ID: The whole ID must contains a namespace and a identifier in
   *     the form NAMESPACE:IDENTIFIER
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  abstract public function constructObject($id = NULL);

  /**
   * This ingests a new object into the repository.
   *
   * @param AbstractObject &$object
   *   The instantiated AbstractObject to ingest into the repository. This
   *   object is passed by reference, and the reference will be replaced by
   *   an object representing the ingested AbstractObject.
   *
   * @return AbstractObject
   *   The ingested abstract object.
   */
  abstract public function ingestObject(NewFedoraObject &$object);

  /**
   * Gets a object from the repository.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return AbstractObject
   *   The requested object.
   */
  abstract public function getObject($id);

  /**
   * Removes an object from the repository.
   *
   * This function removes an object from the repository premenenty. It is a
   * dangerous function since it remvoes an object and all of its history from
   * the repository permenently.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return boolean
   *   TRUE if object was purged.
   */
  abstract public function purgeObject($id);

  /**
   * Search the repository for objects.
   *
   * This function isn't implemented yet.
   *
   * @todo Flesh out the function definition for this.
   */
  abstract public function findObjects(array $search);
}

/**
 * Concrete implementation of the AbstractRepository for Fedora.
 *
 * The parent class has more detailed documentation about how this class can
 * be called as an Array.
 *
 * @see AbstractRepository
 */
class FedoraRepository extends AbstractRepository {

  /**
   * This is an instantiated AbstractCache that we use to make sure we aren't
   * instantiating the same objects over and over.
   *
   * @var AbstractCache
   */
  protected $cache;

  /**
   * This provides some convientent methods for searching the resource index.
   *
   * @var RepositoryQuery
   */
  public $ri;

  public $api;

  protected $queryClass = 'RepositoryQuery';
  protected $newObjectClass = 'NewFedoraObject';
  protected $objectClass = 'FedoraObject';

  /**
   * Constructor for the FedoraRepository Object.
   *
   * @param FedoraApi $api
   *   An instantiated FedoraAPI which will be used to connect to the
   *   repository.
   * @param AbstractCache $cache
   *   An instantiated AbstractCache which will be used to cache fedora objects.
   */
  public function __construct(FedoraApi $api, AbstractCache $cache) {
    $this->api = $api;
    $this->cache = $cache;
    $this->ri = new $this->queryClass($this->api->connection);
  }
  
  /**
   * @see AbstractRepository::findObjects
   * @todo this needs to be implemented!
   */
  public function findObjects(array $search) {
  }

  /**
   * @todo validate the ID
   * @todo catch the getNextPid errors
   *
   * @see AbstractRepository::constructObject
   */
  public function constructObject($id = NULL) {
    $exploded = explode(':', $id);
    if (!$id) {
      $id = $this->api->m->getNextPid();
    }
    elseif (count($exploded) == 1) {
      $id = $this->api->m->getNextPid($exploded[0]);
    }
    return new $this->newObjectClass($id, $this);
  }
  
  /**
   *  @todo validate the ID
   *  @todo catch the getNextPid errors
   *
   *  @see AbstractRepository::getNextIdentifier
   */
  public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1) {
    $pids = array();

    if ($create_uuid) {
      if (is_null($namespace)) {
        $repository_info = $this->api->a->describeRepository();
        $namespace = $repository_info['repositoryPID']['PID-namespaceIdentifier'];
      }
      if ($number_of_identifiers > 1) {
        for ($i = 1; $i <= $number_of_identifiers; $i++) {
          $pids[] = $namespace . ':' . $this->getUuid();
        }
      }
      else {
        $pids = $namespace . ':' . $this->getUuid();
      }
    }
    else {
      $pids = $this->api->m->getNextPid($namespace, $number_of_identifiers);
    }

    return $pids;
  }
  
   /**
   * This method will return a valid UUID based on V4 methods.
   *
   * @return string
   *   A valid V4 UUID.
   */
  protected function getUuid() {
    $bytes = openssl_random_pseudo_bytes(2);
    $add_mask = $this->convertHexToBin('4000');
    $negate_mask = $this->convertHexToBin('C000');
    // Make start with 11.
    $manipulated_bytes = $bytes | $negate_mask;
    // Make start with 01.
    $manipulated_bytes = $manipulated_bytes ^ $add_mask;
    $hex_string_10 = bin2hex($manipulated_bytes);

    return sprintf('%08s-%04s-4%03s-%s-%012s',
      bin2hex(openssl_random_pseudo_bytes(4)),
      bin2hex(openssl_random_pseudo_bytes(2)),
      // Four most significant bits holds version number 4.
      substr(bin2hex(openssl_random_pseudo_bytes(2)), 1),
      // Two most significant bits holds zero and one for variant DCE1.1
      $hex_string_10,
      bin2hex(openssl_random_pseudo_bytes(6))
    );
  }


  /**
   * @see AbstractRepository::ingestObject()
   * @todo error handling
   */
  public function ingestObject(NewFedoraObject &$object) {

    // create an empty fedora object
    $id = $this->api->m->ingest(array('pid' => $object->id,
                                      'label' => $object->label,
                                      'logMessage' => $object->logMessage));
    $fedora_object = new $this->objectClass($id, $this);

    // copy object level properties to the new object
    $fedora_object->state = $object->state;
    //pp changed the below as they crashed on fcrepo4 always had two models only fedora:object and fedora:owned
    //$fedora_object->owner = $object->owner;
    //$fedora_object->models = $object->models;
    $info = $this->api->a->describeRepository();
    
    $datastreams = array();
    // now we have an empty fedora object with pid=$id
    foreach ($object as $ds) {
      // create the empty datastream that we will populate
      $dstream = $fedora_object->constructDatastream($ds->id, $ds->controlGroup);

      // copy the datastream level properties
      $dstream->label = $ds->label;
      $dstream->versionable = $ds->versionable;
      $dstream->state = $ds->state;
      $dstream->mimetype = $ds->mimetype;
      $dstream->format = $ds->format;
      $dstream->size = $ds->size;
     
      $dstream->checksumType = $ds->checksumType;
      $dstream->createdDate = $ds->createdDate; // what about this one
      //$dstream->content = $ds->content; // this is probably wrong, so lets skip it

      // now fetch the content depending on the controlGroup of the original datastream
      if ($ds->controlGroup == 'X'  ) {
        // load the original file
        $file = tempnam(sys_get_temp_dir(), 'tuque');
        $ds->getContent($file);
        $dstream->setContentFromFile($file); // and place it in the new datastream
        unlink($file);
      } 
      else if ($ds->controlGroup == 'M'){
        $file = tempnam(sys_get_temp_dir(), 'tuque');
        $ds->getContent($file);
        $dstream->content = $file;
        //we will unlink the file when after we call addDatastreams
      }      
      else if ($ds->controlGroup == 'E' || $ds->controlGroup == 'R') {
        $dstream->url = $ds->url;
      }
      if($info['repositoryVersion'] < 4.0){
       //attach the datastream to the object
        $fedora_object->ingestDatastream($dstream);
      } else {            
        $datastreams["$ds->id"] = $dstream;     
      }
      
    }
    if($info['repositoryVersion'] >= 4.0){
      $response = $this->api->m->addDatastreams($id, $datastreams);
    }
    $object = $fedora_object;
    $this->cache->set($id, $object);
    return $object;
  }

  /**
   * @see AbstractRepository::getObject()
   * @todo perhaps we should check if an object exists instead of catching
   *   the exception
   */
  public function getObject($id) {
    $object = $this->cache->get($id);
    if ($object !== FALSE) {
      return $object;
    }

    try {
      $object = new $this->objectClass($id, $this);
      $this->cache->set($id, $object);
      return $object;
    }
    catch (RepositoryException $e) {
        throw $e;
    }
  }

  /**
   * @see AbstractRepository::purgeObject()
   */
  public function purgeObject($id) {
    try {
      $this->api->m->purgeObject($id);
      $object = $this->cache->get($id);
      if ($object !== FALSE) {
        return $this->cache->delete($id);;
      }
    }
    catch (RepositoryException $e) {
      // @todo chain exceptions here.
      throw $e;
    }
    //pp changed this return success
    return TRUE;
  }
}
