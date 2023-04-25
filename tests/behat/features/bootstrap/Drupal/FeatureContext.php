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
   * Output the HTML of the page.
   *
   * @Then I output the page
   */
  public function iOutputThePage()
  {
    print PHP_EOL . '******';
    print $this->getSession()->getPage()->getHtml() . PHP_EOL . '******' . PHP_EOL;
  }

  /**
   * @Then I output the content of the page
   */
  public function iOutputTheContentOfThePage()
  {
    print PHP_EOL . '******';
    print $this->getSession()->getPage()->findById('main')->getHtml() . PHP_EOL . '******' . PHP_EOL;
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

  /**
     * Check if the given radio button is selected or not
     *
     * @param $radioButtonSelector
     *   string The label of the radio button
     * @param $selected
     *   boolean To test against selected or not
     *
     * @Given /^I (?:|should )see the radio button "([^"]*)" selected$/
     * @Then /^the radio button "([^"]*)" (?:is|should be) selected$/
     * @Then /^the "([^"]*)" radio button (?:is|should be) selected$/
     */
    public function isRadioButtonSelected($radioButtonSelector, $selected = TRUE) {
      // Verify whether a field with the given selector exists or not
      $field = $this->getSession()->getPage()->findField($radioButtonSelector);
      if (empty($field)) {
        throw new \Exception(sprintf("Form field with id|name|label|value '%s' was not found on the page %s", $radioButtonSelector, $this->getSession()->getCurrentUrl()));
      }
      // Verify if the field is a radio button or not
      $type = $field->getAttribute('type');
      if ($type != "radio") {
        throw new \Exception(sprintf("The field '%s' was found but it is not a radio button on the page %s", $radioButtonSelector, $this->getSession()->getCurrentUrl()));
      }
      // If the field should be selected, then the attribute 'checked' should exist
      if ($selected) {
        if (!$field->hasAttribute('checked')) {
          throw new \Exception(sprintf("The radio button '%s' was not selected on the page %s", $radioButtonSelector, $this->getSession()->getCurrentUrl()));
        }
      }
      else {
        if ($field->hasAttribute('checked')) {
          throw new \Exception(sprintf("The radio button '%s' was selected on the page %s", $radioButtonSelector, $this->getSession()->getCurrentUrl()));
        }
      }
    }

    /**
     * @Given I wait :num seconds
     */
    public function iWaitSeconds($num)
    {
      sleep($num);
    }

  /**
   * Check if the given title is expected or not.
   *
   * @Then the page title should be :expectedTitle
   */
  public function thePageTitleShouldBe($expectedTitle) {
    $titleElement = $this->getSession()->getPage()->find('css', 'head title');
    if ($titleElement === null) {
      throw new \Exception('Page title element was not found!');
    }
    else {
      $title = $titleElement->getText();
      if ($expectedTitle !== $title) {
        throw new \Exception("Incorrect title! Expected:$expectedTitle | Actual:$title ");
      }
    }
  }

  /**
   * Check if the given element is expected or not.
   *
   * @Then the :element element should have the value :value
   */
  public function iShouldSeeValueElement($element, $value) {
    $page = $this->getSession()->getPage();
    $element_value = $page->find('named', ['field', $element])->getValue();
    if (strpos($element_value, $value) === false) {
      throw new \Exception('Value ' . $value . ' not found in element ' . $element . ', which had a value of ' . $element_value . '.');
    }
  }

  /**
   * Check if the given disabled element value is expected or not.
   *
   * @Then the disabled :element element should have the value :value
   */
  public function iShouldSeeValueOnDisabledElement($element, $value) {
    $page = $this->getSession()->getPage();
    $element_value = $page->find('named', ['field', $element])->getAttribute('value');
    if ($element_value !== $value) {
      throw new \Exception('Value: ' . $value . ' not found in element ' . $element . ', which had a value of ' . $element_value . '.');
    }
  }

  /**
   * Check if the given elementlabel exists or not.
   *
   * @Then the :elementlabel element should exists
   */
  public function fieldShouldExists($elementlabel) {
    $page = $this->getSession()->getPage();
    $element = $page->findField($elementlabel);
    if (empty($element)) {
      throw new \Exception(sprintf("Form element with label '%s' was not found on the page %s", $elementlabel, $this->getSession()->getCurrentUrl()));
    }
  }

  /**
   * Check if the given element is enabled or not.
   *
   * @param $elementlabel
   *   string The label of the field
   *
   * @param $status
   *   string of "enabled" or "disabled"
   *
   * @Then the element :elementlabel is :status
   */
  public function fieldShouldInputable($elementlabel, $status) {
    $field = $this->getSession()->getPage()->find('named', ['field', $elementlabel]);
    if ($status == 'enabled') {
      if ($field->getAttribute('disabled') == 'disabled') {
        throw new \Exception('field '. $elementlabel. ' is disabled ');
      }
    }
    if ($status == 'disabled') {
      if ($field->getAttribute('disabled') != 'disabled') {
        throw new \Exception('field ' . $elementlabel . ' is enabled ');
      }
    }
  }

  /**
   * Expand the clicable section on the node edit page.
   *
   * @Then I expand the :section_name
   */
  public function iExpandThe($section_name) {
    $summaries = $this->getSession()->getPage()->findAll('css', '.seven-details__summary');
    $match = FALSE;
    foreach ($summaries as $summary) {
      $name = $summary->getText();
      if (stripos($name, $section_name) !== false) {
        $match = $summary;
        break;
      }
    }
    if ($match) {
      $match->click();
      $this->getSession()->wait(1000);
    }
    else {
      throw new \Exception('Node edit section "' . $section_name . '" was not found.');
    }
  }

  /**
   * @Then I input the :value to :field_name in the node edit page
   */
  public function iInputTheToInTheNodeEditPage($value, $field_name) {
    $page = $this->getSession()->getPage();
    $field = $page->findField($field_name);
    if (empty($field)) {
      throw new \Exception('Node edit field "' . $field_name . '" was not found.');
    } else {
      $field->setValue($value);
    }
  }

  /**
   * Set field value by field id in the node edit page.
   *
   * @Then I set :value to :field_id
   */
  public function iSetValueTo($value, $field_id) {
    $page = $this->getSession()->getPage();
    $field = $page->find('css', $field_id);
    $this->getSession()->wait(1000);
    if (empty($field)) {
      throw new \Exception('Node edit field "' . $field_id . '" was not found.');
    } else {
      $field->setValue($value);
    }
  }

  /**
   * Fills in specified field with date field
   * Example: When I fill in "field_ID" with date "now"
   * Example: When I fill in "field_ID" with date "-7 days"
   * Example: When I fill in "field_ID" with date "+7 days"
   * Example: When I fill in "field_ID" with date "-/+0 weeks"
   * Example: When I fill in "field_ID" with date "-/+0 years"
   *
   * @When /^(?:|I )fill in "(?P<field>(?:[^"]|\\")*)" with date "(?P<value>(?:[^"]|\\")*)"$/
   */
  public function fillDateField($field, $value) {
    $newDate = strtotime("$value");

    $dateToSet = date("d/m/Y", $newDate);
    $this->iSetValueTo($dateToSet, $field);
    // $this->getSession()->getPage()->fillField($field, $dateToSet);
  }

  /**
   * Show field value by field id in the node edit page.
   *
   * @Then show field value :field_id
   */
  public function showFieldValue($field_id) {
    $page = $this->getSession()->getPage();
    $field = $page->find('css', $field_id);
    $this->getSession()->wait(1000);
    if (empty($field)) {
      throw new \Exception('Node edit field "' . $field_id . '" was not found.');
    } else {
      $value = $field->getAttribute('value');
      echo "field value: ". $value;
    }
  }

  /**
   * @When /^I click li option "([^"]*)"$/
   *
   * @param $text
   * @throws \InvalidArgumentException
   */
  public function iClickLiOption($text) {
    $lis = $this->getSession()->getPage()->findAll('css', '.vertical-tabs__menu li.vertical-tabs__menu-item');
    $match = FALSE;
    foreach ($lis as $li) {
      $name = $li->getText();
      if (stripos($name, $text) !== false) {
        $match = $li;
        break;
      }
    }
    if ($match) {
      $match->click();
      $this->getSession()->wait(1000);
    } else {
      throw new \InvalidArgumentException(sprintf('Cannot find li section: "%s"', $text));
    }
  }
}
