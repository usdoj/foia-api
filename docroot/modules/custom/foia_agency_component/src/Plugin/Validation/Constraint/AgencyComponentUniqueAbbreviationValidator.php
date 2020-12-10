<?php
/**
 * @file
 * AgencyComponentUniqueAbbreviationValidator class.
 */

namespace Drupal\foia_agency_component\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityPublishedInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the AgencyComponentUniqueAbbreviation constraint.
 */
class AgencyComponentUniqueAbbreviationValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {

    $entity = $this->context->getRoot()->getValue();

    if (count($value) > 0) {
      $abbreviation = $value[0]->value;
      $agency = $entity->get('field_agency')->first()->getValue()['target_id'];
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'agency_component')
        ->condition('field_agency_comp_abbreviation', $abbreviation)
        ->condition('field_agency', $agency);
      $results = $query->execute();
      if (!empty($results)) {
          $this->context->addViolation($constraint->notUnique, [
            '%abbreviation' => $abbreviation,
          ]);
      }
    }
  }
}
