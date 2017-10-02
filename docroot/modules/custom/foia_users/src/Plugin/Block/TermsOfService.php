<?php

namespace Drupal\foia_users\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Terms of Service' block.
 *
 * @Block(
 *   id = "foia_users_tos",
 *   admin_label = @Translation("Terms of Service"),
 *   category = @Translation("FOIA User"),
 * )
 */
class TermsOfService extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->getText(),
    ];
  }

  /**
   * Returns the ToS text.
   *
   * @return string
   *   Returns the ToS text.
   */
  public function getText() {
    $text = '<p>' . $this->t('You are accessing a U.S. Government information system, which includes: (1) this computer, (2) this computer network, (3) all computers connected to this network, and (4) all devices and storage media attached to this network or to a computer on this network. This information system is provided for U.S. Government-authorized use only. Unauthorized or improper use of this system may result in disciplinary action, and civil and criminal penalties.') . '</p>';
    $text .= '<p>' . $this->t('By using this information system, you understand and consent to the following:') . '<br>';
    $text .= t('- You have no reasonable expectation of privacy regarding any communications transmitted through or data stored on this information system. At any time, the government may monitor, intercept, search and/or seize data transiting or stored on this information system.') . '<br>';
    $text .= t('- Any communications transmitted through or data stored on this information system may be disclosed or used for any U.S. Government-authorized purpose.') . '</p>';
    $text .= '<p>' . $this->t('For further information see the Department order on Use and Monitoring of Department Computers and Computer Systems.') . '</p>';

    return $text;
  }

}
