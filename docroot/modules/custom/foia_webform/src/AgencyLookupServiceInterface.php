<?php

namespace Drupal\foia_webform;

/**
 * Interface AgencyLookupServiceInterface.
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
  public function getComponentByWebform($webformId);

}
