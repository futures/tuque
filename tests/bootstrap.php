<?php

/**
 * @file
 * Bootstrap code runs before any PHPUnit tests.
 */

// Append the Tuque ROOT directory as the include path.
define('TUQUE_ROOT', realpath(__DIR__ . '/..'));

// Set the include path to be the Tuque root directory.
set_include_path(get_include_path() . PATH_SEPARATOR . TUQUE_ROOT);

// Load all the know repositories to test against.
