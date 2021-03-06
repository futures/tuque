<?php

/**
 * @file
 */

// Note this isn't in the tuque namespace.

/**
 * An abstract repository interface.
 *
 * This is the minimal set of functions a repository implementation must have.
 */
interface AbstractRepository {

  /**
   * Returns basic information about the Repository.
   *
   * This is listed as an unimplemented function in the official API for Fedora.
   * However other libraries connecting to the Fedora REST interaface use this
   * so we are including it here. It may change in the future.
   *
   * @throws RepositoryException
   *
   * @return array()
   *   An array describing the repository containing at least the following
   *   fields.
   *
   * @code
   *   Array
   *   (
   *       [repositoryName] => Fedora Repository
   *       [repositoryBaseURL] => http://localhost:8080/fedora
   *       [repositoryVersion] => 3.4.1
   *       [authenticated] => TRUE
   *   )
   * @endcode
   */
  public function describe();

  /**
   * This method is a factory that will return a new repositoryobject object.
   *
   * That can be manipulated and then ingested into the repository.
   *
   * @param string $id
   *   The ID to assign to this object. There are three options:
   *   - NULL: An ID will be assigned.
   *   - A namespace: An ID will be assigned in this namespace.
   *   - A whole ID: The whole ID must contains a namespace and a identifier in
   *     the form NAMESPACE:IDENTIFIER
   * @param bool $create_uuid
   *   Indicates if the objects ID should contain a UUID.
   *
   * @return AbstractObject
   *   Returns an instantiated AbstractObject object that can be manipulated.
   *   This object will not actually be created in the repository until the
   *   ingest method is called.
   */
  public function constructObject($id = NULL, $create_uuid = FALSE);

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
  public function ingestObject(AbstractObject &$object);

  /**
   * Gets a object from the repository.
   *
   * @param string $id
   *   The identifier of the object.
   *
   * @return AbstractObject
   *   The requested object.
   */
  public function getObject($id);

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
   * @return bool
   *   TRUE if object was purged.
   */
  public function purgeObject($id);

  /**
   * Search the repository for objects.
   *
   * This function isn't implemented yet.
   *
   * @todo Flesh out the function definition for this.
   */
  public function findObjects(array $search);

  /**
   * Will return an unused identifier for an object.
   *
   * @note
   *   It is not mathematically impossible to have collisions if the
   *   $create_uuid parameter is set to true.
   *
   * @param mixed $namespace
   *   NULL if we should use the default namespace.
   *   string the namespace to be used for the identifier.
   * @param bool $create_uuid
   *   True if a V4 UUID should be used as part of the identifier.
   * @param int $number_of_identifiers
   *   The number of identifers to return
   *   Defaults to 1.
   *
   * @return mixed
   *   string An identifier for an object.
   *   array  An array of identifiers for an object.
   *
   * @code
   *   Array('test:7', test:8)
   * @endcode
   */
  public function getNextIdentifier($namespace = NULL, $create_uuid = FALSE, $number_of_identifiers = 1);

}
