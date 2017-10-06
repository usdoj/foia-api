<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;

/**
 * Provides interface defining the Agency Lookup Service.
 */
interface AgencyLookupServiceInterface {

  /**
   * Looks up the Agency Component based on the associated webform.
   *
   * @param string $webformId
   *   The form ID.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The Agency Component object or NULL.
   */
  public function getComponentFromWebform($webformId);

  /**
   * Look up the Agency taxonomy term for a given Agency Component.
   *
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   Returns the Agency taxonomy term object or NULL.
   */
  public function getAgencyFromComponent(NodeInterface $agencyComponent);

}
