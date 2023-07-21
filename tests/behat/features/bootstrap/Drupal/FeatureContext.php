<?php

namespace Drupal;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\PyStringNode;
use Drupal\webform\Entity\Webform;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\node\Entity\Node;

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
   * Fix some problems that the Drupal extension causes with date fields.
   *
   * @afterNodeCreate
   */
  public function fixNodeDateFieldValues(AfterNodeCreateScope $event) {
    $entity = $event->getEntity();
    if (isset($entity->field_rep_start) && isset($entity->nid)) {
      $existing_date = $entity->field_rep_start[0];
      $fixed_date = str_replace('T06:00:00', '', $entity->field_rep_start[0]);
      if ($existing_date != $fixed_date) {
        $node = Node::load($entity->nid);
        $node->set('field_rep_start', $fixed_date);
        $node->save();
      }
    }
  }

  /**
   * Create aliases based on provided path_alias value.
   *
   * @afterNodeCreate
   */
  public function nodePathAliasPostSave(AfterNodeCreateScope $event) {
    $entity = $event->getEntity();
    if (isset($entity->path_alias) && isset($entity->nid)) {
      $path_alias = PathAlias::create([
        'path' => '/node/' . $entity->nid,
        'alias' => $entity->path_alias,
        'langcode' => 'en',
      ]);
      $path_alias->save();
    }
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
    $baseUrl = $this->getMinkParameter('base_url');
    $destinationUrl = "{$baseUrl}/user/{$uid}";
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

    $driver = $this->getSession()->getDriver();
    $class = get_class($driver);


    // If javascript is enabled then we need to get the page title using JS
    switch ($class) {
      case "Behat\Mink\Driver\Selenium2Driver":
        $title = $this->getSession()->evaluateScript("return document.title");
        break;
      case "Behat\Mink\Driver\GoutteDriver":
      default:
        $title = $this->getSession()->getPage()->find('css', 'head title')->getText();
        break;
    }

    if ($title === null) {
      throw new \Exception('Page title element was not found!');
    }
    else {
      if ($expectedTitle !== $title) {
        throw new \Exception("Incorrect title! Expected:$expectedTitle | Actual: $title ");
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
   * Expand the clickable section on the node edit page.
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

    $dateToSet = date("m/d/Y", $newDate);
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
   * @Given I ignore the admin menu
   */
  public function iIgnoreTheAdminMenu() {
    $this->getSession()->getDriver()->evaluateScript(
      "jQuery('#toolbar-administration,#toolbar-item-administration').hide();"
    );
  }

  /**
   * @When I click :link in the :section section
   */
  public function iClickInTheSection($link, $section) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    $section_link = $page->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_id = $section_link->getAttribute('href');
    $section_element = $page->find('css', $section_id);
    $link_inside_section = $section_element->find('named', ['link', $link]);
    if (empty($link_inside_section)) {
      throw new \Exception('Link "' . $link . '" inside section "' . $section . '" was not found.');
    }
    $link_inside_section->click();
  }

  /**
   * @When I select :option from :select in the :section section
   */
  public function iSelectFromInTheSection($option, $select, $section) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    $section_link = $sidebar_element->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_id = $section_link->getAttribute('href');
    $section_element = $page->find('css', $section_id);
    $select_inside_section = $section_element->find('named', ['select', $select]);
    if (empty($select_inside_section)) {
      throw new \Exception('Select "' . $select . '" inside section "' . $section . '" was not found.');
    }
    $select_inside_section->selectOption($option);
  }

  /**
   * @When for :input in the :section section I enter :value
   */
  public function forInTheSectionIEnter($input, $section, $value) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    $section_link = $sidebar_element->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_id = $section_link->getAttribute('href');
    $section_element = $page->find('css', $section_id);
    $input_inside_section = $section_element->find('named', ['field', $input]);
    if (empty($input_inside_section)) {
      throw new \Exception('Input "' . $input . '" inside section "' . $section . '" was not found.');
    }
    $input_inside_section->setValue($value);
  }

  /**
   * @When for :input in the :section section and the :subsection sub-section I enter :value
   */
  public function forInTheSectionAndTheSubSectionIEnter($input, $section, $subsection, $value) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    $section_link = $sidebar_element->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_id = $section_link->getAttribute('href');
    $section_element = $page->find('css', $section_id);
    $subsection_link = $section_element->find('named', ['link', $subsection]);
    if (empty($subsection_link)) {
      throw new \Exception('Sub-section "' . $subsection . '" was not found.');
    }
    $subsection_id = $subsection_link->getAttribute('href');
    $subsection_element = $page->find('css', $subsection_id);
    $input_inside_subsection = $subsection_element->find('named', ['field', $input]);
    if (empty($input_inside_subsection)) {
      throw new \Exception('Input "' . $input . '" inside section "' . $section . '" and sub-section "' . $subsection . '" was not found.');
    }
    $input_inside_subsection->setValue($value);
  }

  /**
   * @When I select :option from :select in the :section section and the :subsection sub-section
   */
  public function iSelectFromInTheSectionAndSubsection($option, $select, $section, $subsection) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    $section_link = $sidebar_element->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_id = $section_link->getAttribute('href');
    $section_element = $page->find('css', $section_id);
    $subsection_link = $section_element->find('named', ['link', $subsection]);
    if (empty($subsection_link)) {
      throw new \Exception('Sub-section "' . $subsection . '" was not found.');
    }
    $subsection_id = $subsection_link->getAttribute('href');
    $subsection_element = $page->find('css', $subsection_id);
    $select_inside_subsection = $subsection_element->find('named', ['select', $select]);
    if (empty($select_inside_subsection)) {
      throw new \Exception('Select "' . $select . '" inside section "' . $section . '" and sub-section "' . $subsection . '" was not found.');
    }
    $select_inside_subsection->selectOption($option);
  }

  /**
   * @When I click the section :section
   */
  public function iClickTheSection($section) {
    $page = $this->getSession()->getPage();
    $sidebar_element = $page->find('css', '.vertical-tabs__menu');
    if (empty($sidebar_element)) {
      throw new \Exception('There is no sidebar vertical tabs menu on this page - section not found.');
    }
    $section_link = $sidebar_element->find('named', ['link', $section]);
    if (empty($section_link)) {
      throw new \Exception('Section "' . $section . '" was not found.');
    }
    $section_link->click();
  }

  /**
   * @Given I click the edit tab
   */
  public function iClickTheEditTab() {
    $primary_tabs = $this->getSession()->getPage()->find('css', '.tabs.primary');
    $edit_button = $primary_tabs->find('named', ['link', 'Edit']);
    if (empty($edit_button)) {
      throw new \Exception('Edit tab was not found.');
    }
    $edit_button->click();
  }

  /**
   * @Given I press the save button at the bottom of the page
   */
  public function iPressTheSaveButton() {
    $buttons = $this->getSession()->getPage()->find('css', '#edit-actions');
    $save_button = $buttons->find('named', ['button', 'Save']);
    if (empty($save_button)) {
      throw new \Exception('Save button was not found.');
    }
    $save_button->click();
  }

  /**
   * @Given I fill in :arg1 field with :arg2
   */
  public function fillCKEditor($locator, $value) {
    // Make sure we can get to the field first
    $el = $this->getSession()->getPage()->findField($locator);
    if (empty($el)) {
      throw new \Exception('Could not find WYSIWYG with locator: ' . $locator, $this->getSession());
    }
    $fieldId = $el->getAttribute('id');
    if (empty($fieldId)) {
      throw new \Exception('Could not find an id for field with locator: ' . $locator);
    }

    $lowercase = strtolower($locator);
    $editor = "div.js-form-item-$lowercase-0-value .ck-editor__editable";

    $this->getSession()
      ->executeScript(
        "
        var domEditableElement = document.querySelector(\"$editor\");
        if (domEditableElement.ckeditorInstance) {
          const editorInstance = domEditableElement.ckeditorInstance;
          if (editorInstance) {
            editorInstance.setData(\"$value\");
            return 'Success!';
          } else {
            throw new Exception('Could not get the editor instance!');
          }
        } else {
          throw new Exception('Could not find the element!');
        }
        ");
  }
}
