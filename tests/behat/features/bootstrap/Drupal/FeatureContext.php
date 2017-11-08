<?php

namespace Drupal;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\webform\Entity\Webform;

/**
 * FeatureContext class defines custom step definitions for Behat.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @var
   */
  private $url;

  /**
   * Every scenario gets its own context instance.
   *
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {

  }

  /**
   * Visits the delete path for the current entity.
   *
   * @Given I attempt to delete the current entity
   */
  public function iAttemptToDeleteTheCurrentEntity() {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $destinationUrl = "{$currentUrl}/delete";
    $this->getSession()->visit($destinationUrl);
  }

  /**
   * Deletes a user for cleanup purposes.
   *
   * @Then the user :arg1 is deleted
   */
  public function theUserIsDeleted($arg1) {
    if (!empty($arg1)) {
      $user = user_load_by_name($arg1);
      $uid = $user->get('uid')->value;
      user_cancel(array(), $uid, 'user_cancel_delete');
    }
  }

  /**
   * Visits a user page by username.
   *
   * @Then I view the user :arg1
   */
  public function iViewTheUser($arg1) {
    $user = user_load_by_name($arg1);
    $uid = $user->get('uid')->value;
    $destinationUrl = "user/{$uid}";
    $this->getSession()->visit($destinationUrl);
  }

  /**
   * Cleans up taxonomy terms created during testing.
   *
   * @AfterScenario @agency
   */
  public function cleanTaxonomyTerms(AfterScenarioScope $scope) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('name', "A Test", 'STARTS_WITH');
    $tids = $query->execute();
    $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entities = $controller->loadMultiple($tids);
    $controller->delete($entities);
  }

  /**
   * Saves the current URL into a variable.
   *
   * @Then save the current URL
   */
  public function saveTheCurrentUrl()
  {
    $this->url = $this->getSession()->getCurrentUrl();
  }

  /**
   * Retrieves previously saved URL.
   *
   * @When I go to saved URL
   */
  public function iGoToSavedUrl()
  {
    $this->getSession()->visit($this->url);
  }

  /**
   * @Given I create a webform :arg1
   */
  public function iCreateAWebform($arg1)
  {
    if (!empty($arg1)) {
      Webform::create(['id' => $arg1])->save();
    }
  }

  /**
   * @When I go to the :arg1 type entity with the :arg2 label
   */
  public function iGoToTheTypeEntityWithTheLabel($entityType, $label) {
    if (\Drupal::entityTypeManager()->getDefinition($entityType)) {
      switch ($entityType) {
        case 'node':
          $labelField = 'title';
          $path = 'node';
          break;
        case 'taxonomy_term':
          $labelField = 'name';
          $path = 'taxonomy/term';
          break;
        case 'user':
          $labelField = 'name';
          $path = 'user';
          break;
      }
      $entities = \Drupal::entityTypeManager()
        ->getStorage($entityType)
        ->loadByProperties([$labelField => $label]);
      if ($entity = reset($entities)) {
        $eid = $entity->id();
        $alias = \Drupal::service('path.alias_manager')->getAliasByPath("/{$path}/{$eid}");
        $this->getSession()->visit($alias);
      }
    }
  }

  /**
   * @Given I edit the current entity
   */
  public function iEditTheCurrentEntity()
  {
    $currentPath = $this->getSession()->getCurrentUrl();
    $newPath = "{$currentPath}/edit";
    $this->getSession()->visit($newPath);
  }

}
