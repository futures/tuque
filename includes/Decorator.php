<?php

/**
 * @file
 * This file defines an abstract decorator.
 */

namespace Tuque;

class Decorator {

  /**
   * This is the delegate being decorated.
   * @var object
   */
  protected $delegate;

  /**
   * Constructor.
   *
   * @param object $delegate
   *   The delegate that is being decorated.
   */
  public function __construct($delegate) {
    $this->delegate = $delegate;
  }

  /**
   * Get the given attribute of the delegate.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __get($name) {
    return $this->delegate->$name;
  }

  /**
   * Check if the given attribute of the delegate is set.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __isset($name) {
    return isset($this->delegate->$name);
  }

  /**
   * Set the given attribute of the delegate.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __set($name, $value) {
    $this->delegate->$name = $value;
  }

  /**
   * Un set the given attribute of the delegate.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __unset($name) {
    unset($this->delegate->$name);
  }

  /**
   * Call the given method of the delegate.
   *
   * @see http://php.net/manual/en/language.oop5.overloading.php
   */
  public function __call($method, $arguments) {
    return call_user_func_array(array($this->delegate, $method), $arguments);
  }

}
