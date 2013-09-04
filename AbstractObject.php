<?php

/**
 * @file
 * Wrapper class for the Repository Object class implementation.
 */

// Note this isn't in the tuque namespace.

/**
 * An abstract class defining a Object in the repository. This is the class
 * that needs to be implemented in order to create new repository backends
 * that can be accessed using Tuque.
 *
 * These classes implement the php object array interfaces so that the object
 * can be accessed as an array. This provides access to datastreams. The object
 * is also traversable with foreach, so that each datastream can be accessed.
 *
 * @code
 * $object = new AbstractObject()
 *
 * // access every object
 * foreach ($object as $dsid => $dsObject) {
 *   // print dsid and set contents to "foo"
 *   print($dsid);
 *   $dsObject->content = 'foo';
 * }
 *
 * // test if there is a datastream called 'DC'
 * if (isset($object['DC'])) {
 *   // if there is print its contents
 *   print($object['DC']->content);
 * }
 *
 * @endcode
 */
interface AbstractObject extends Countable, ArrayAccess, IteratorAggregate {

  /**
   * The label for this object.
   *
   * @var string
   *
  public $label;
  /**
   * The user who owns this object.
   *
   * @var string
   *
  public $owner;
  /**
   * The state of this object. Must be one of: A (Active), I (Inactive) or
   * D (Deleted). This is a required property and cannot be unset.
   *
   * @var string
   *
  public $state;
  /**
   * The identifier of the object.
   *
   * @var string
   *
  public $id;
  /**
   * The date that the object was created. Only valid for objects that have
   * been ingested.
   *
   * @var FedoraDate
   *
  public $createdDate;
  /**
   * The date the object was last modified.
   *
   * @var FedoraDate
   *
  public $lastModifiedDate;
  /**
   * Log message associated with the creation of the object in Fedora.
   *
   * @var string
   *
  public $logMessage;
  /**
   * An array of strings containing the content models of the object.
   *
   * @var array
   *
  public $models;
  /**
   * Boolean specifying if the object has been ingested into the repository.
   *
   * @var boolean
   *
  public $ingested;

  /**
   * Set the state of the object to deleted.
   */
  public function delete();

  /**
   * Get a datastream from the object.
   *
   * @param string $id
   *   The id of the datastream to retreve.
   *
   * @return AbstractDatastream
   *   Returns FALSE if the datastream could not be found. Otherwise it return
   *   an instantiated Datastream object.
   */
  public function getDatastream($id);

  /**
   * Purges a datastream.
   *
   * @param string $id
   *   The id of the datastream to purge.
   *
   * @return boolean
   *   TRUE on success. FALSE on failure.
   */
  public function purgeDatastream($id);

  /**
   * Factory to create new datastream objects. Creates a new datastream object,
   * this object is not ingested into the repository until you call
   * ingestDatastream.
   *
   * @param string $id
   *   The identifier of the new datastream.
   * @param string $control_group
   *   The control group the new datastream will be created in.
   *
   * @return AbstractDatastream
   *   Returns an instantiated Datastream object.
   */
   public function constructDatastream($id, $control_group = 'M');

  /**
   * Ingests a datastream object into the repository.
   */
   public function ingestDatastream(&$ds);

  /**
   * Unsets public members.
   *
   * We only define the public members of the object for Doxygen, they aren't actually accessed or used,
   * and if they are not unset, they can cause problems after unserialization.
   *
  public function __construct() {
    $this->unset_members();
  }

  /**
   * Upon unserialization unset any public members.
   *
  public function __wakeup() {
    $this->unset_members();
  }

  /**
   * Unsets public members, required for child classes to funciton properly with MagicProperties.
   *
  private function unset_members() {
    unset($this->id);
    unset($this->state);
    unset($this->createdDate);
    unset($this->lastModifiedDate);
    unset($this->label);
    unset($this->owner);
    unset($this->logMessage);
    unset($this->models);
  }*/

}
