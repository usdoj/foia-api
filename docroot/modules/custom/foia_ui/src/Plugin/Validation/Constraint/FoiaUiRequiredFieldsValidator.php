<?php

namespace Drupal\foia_ui\Plugin\Validation\Constraint;

use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the API required fields for Agency Components.
 */
class FoiaUiRequiredFieldsValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if ($entity->bundle() === 'agency_component') {
      $submissionFormat = $entity->get('field_portal_submission_format')->value;
      if ($submissionFormat === 'api') {
        $apiUrl = $entity->get('field_submission_api')->getString();
        $apiSecret = $entity->get('field_submission_api_secret')->getString();
        if (!$apiUrl) {
          $this->context->addViolation($constraint->urlRequired);
        }
        if (!UrlHelper::isValid($apiUrl, TRUE)) {
          $this->context->addViolation($constraint->apiUrlNotValid, ['%url' => $apiUrl]);
        }
        if (!$apiSecret) {
          $this->context->addViolation($constraint->secretRequired);
        }
      }
      elseif ($submissionFormat === 'email') {
        $emailAddress = $entity->get('field_submission_email')->getString();
        if (!$emailAddress) {
          $this->context->addViolation($constraint->emailRequired);
        }
      }
    }
  }

}
