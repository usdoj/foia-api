<?php

namespace Drupal\Tests\foia_webform\Kernel;

/**
 * Trait ReflectionTrait.
 *
 * Package the webform.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
trait ReflectionTrait {

  /**
   * Facilitates access to test private and protected methods.
   *
   * @param mixed &$object
   *   Object to invoke the method on.
   * @param string $methodName
   *   Method to invoke.
   * @param array $parameters
   *   Parameters to pass to the method invocation.
   *
   * @return mixed
   *   Returns according to the invoked method.
   */
  public function invokeMethod(&$object, $methodName, array $parameters = []) {
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(TRUE);

    return $method->invokeArgs($object, $parameters);

  }

  /**
   * Sets a protected property on an object.
   *
   * @param mixed &$object
   *   Object to set a property on.
   * @param string $propertyName
   *   Property to set.
   * @param mixed $value
   *   Value to set the property to.
   */
  public function setProtectedProperty(&$object, $propertyName, $value) {
    $reflection = new \ReflectionClass(get_class($object));
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(TRUE);
    $property->setValue($object, $value);
  }

}
