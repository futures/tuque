<?php

/**
 * @file
 * Defines the AbstractObject interface.
 */

namespace {

  /**
   * An abstract class defining a Object in the repository. This is the class
   * that needs to be implemented in order to create new repository backends
   * that can be accessed using Tuque.
   *
   * These classes implement the php object array interfaces so that the object
   * can be accessed as an array. This provides access to datastreams. The
   * object is also traversable with foreach, so that each datastream can be
   * accessed.
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
     * @return bool
     *   TRUE on success. FALSE on failure.
     */
    public function purgeDatastream($id);

    /**
     * Factory to create new datastream objects.
     *
     * Creates a new datastream object, this object is not ingested into the
     * repository until you call ingestDatastream.
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
  }
}

namespace Tuque {

  /**
   * This class acts as a wrapper for the actual Object implementation.
   */
  class Object extends Decorator implements \AbstractObject {

    /**
     * Constructor for the Repository object.
     *
     * @param AbstractObject $object
     *   The object this object wraps.
     */
    public function __construct(\AbstractObject $object) {
      parent::__construct($object);
    }
    // @todo Have wrappers for Datastream objects.
  }

  /**
   * This class acts as a wrapper for the actual NewObject implementation.
   */
  class NewObject extends Decorator implements \AbstractObject {

    /**
     * Constructor for the Repository object.
     *
     * @param AbstractObject $object
     *   The object this object wraps.
     */
    public function __construct(\AbstractObject $object) {
      parent::__construct($object);
    }
    // @todo Have wrappers for Datastream objects.
  }
}
