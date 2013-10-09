<?php

/**
 * @file
 * Defines the AbstractObject interface.
 */

namespace {
  /**
   * This class can be overriden by anything implementing a datastream.
   */
  interface AbstractDatastream {

    /**
     * This will set the state of the datastream to deleted.
     */
    public function delete();

    /**
     * Set the contents of the datastream from a file.
     *
     * @param string $file
     *   The full path of the file to set to the contents of the datastream.
     */
    public function setContentFromFile($file);

    /**
     * Set the contents of the datastream from a URL.
     *
     * The contents of this URL will be fetched, and the datastream will be
     * updated to contain the contents of the URL.
     *
     * @param string $url
     *   The full URL to fetch.
     */
    public function setContentFromUrl($url);

    /**
     * Set the contents of the datastream from a string.
     *
     * @param string $string
     *   The string whose contents will become the contents of the datastream.
     */
    public function setContentFromString($string);

    /**
     * Get the contents of a datastream and output it to the file provided.
     *
     * @param string $file
     *   The path of the file to output the contents of the datastream to.
     *
     * @return bool
     *   TRUE on success or FALSE on failure.
     */
    public function getContent($file);
  }
}

namespace Tuque {

  /**
   * This class acts as a wrapper for the actual Object implementation.
   */
  class Datastream extends Decorator implements \AbstractDatastream {

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
  class NewDatastream extends Decorator implements \AbstractDatastream {

    /**
     * Constructor for the Repository object.
     *
     * @param AbstractDatastream $datastream
     *   The object this object wraps.
     */
    public function __construct(\AbstractDatastream $datastream) {
      parent::__construct($datastream);
    }
    // @todo Have wrappers for Datastream objects.
  }
}
