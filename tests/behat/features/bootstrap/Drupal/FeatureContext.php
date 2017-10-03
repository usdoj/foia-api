<?php

namespace Drupal;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;

/**
 * FeatureContext class defines custom step definitions for Behat.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Every scenario gets its own context instance.
   *
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {

  }

  /**
   * @Given I attempt to delete the current entity
   */
  public function iAttemptToDeleteTheCurrentEntity()
  {

  $currentUrl = $this->getSession()->getCurrentUrl();
  $destinationUrl = "{$currentUrl}/delete";
  $this->getSession()->visit($destinationUrl);

  }

  /**
   * @Then the user :arg1 is deleted
   */
  public function theUserIsDeleted($arg1)
  {
    if(!empty($arg1)) {
      $user = user_load_by_name($arg1);
      $uid = $user->get('uid')->value;
      user_cancel(array(), $uid, 'user_cancel_delete');
    }
  }

  /**
   * @Then I view the user :arg1
   */
  public function iViewTheUser($arg1)
  {
    $user = user_load_by_name($arg1);
    $uid = $user->get('uid')->value;
    $destinationUrl = "user/{$uid}";
    $this->getSession()->visit($destinationUrl);

  }

}
