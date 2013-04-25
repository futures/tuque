<?php
/**
 * @file
 * This file defines an abstract repository that can be overridden and also
 * defines a concrete implementation for Fedora.
 */

require_once "AbstractRepository.php";
require_once "implementations/fedora4/Object.php";

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

  public $api;

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
   * @todo implement create_uuid
   *
   * @see AbstractRepository::constructObject
   */
  public function constructObject($id = NULL, $create_uuid = FALSE) {
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
    //$info = $this->api->a->describeRepository();

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
      $datastreams["$ds->id"] = $dstream;
    }
    //$response = $this->api->m->addDatastreams($id, $datastreams);
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

  /**
   * @todo implement this.
   */
  public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1) {
  }
}
