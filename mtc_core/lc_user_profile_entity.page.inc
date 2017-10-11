<?php

/**
 * @file
 * Contains user_profile_entity.page.inc.
 *
 * Page callback for User profile entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User profile entity templates.
 *
 * Default template: lc_user_profile_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_lc_user_profile_entity(array &$variables) {
  // Fetch UserProfileEntity Entity Object.
  $lc_user_profile_entity = $variables['elements']['#lc_user_profile_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
