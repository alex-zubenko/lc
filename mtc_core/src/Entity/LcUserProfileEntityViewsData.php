<?php

namespace Drupal\mtc_core\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for User profile entity entities.
 */
class LcUserProfileEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
