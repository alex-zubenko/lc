<?php

/**
 * @file
 * Contains custom tokens and operations over existing ones.
 */

use Drupal\Core\Render\BubbleableMetadata;

use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Unicode;

/**
 * Implements hook_token_info_alter().
 */
function mtc_core_token_info_alter(&$info) {
  $info['tokens']['current-page']['page-number-text'] = array(
    'name' => t('Page number with text'),
    'description' => t('The page number of the current page when viewing paged lists with text.'),
  );
}

/**
 * Implements hook_tokens().
 */
function mtc_core_tokens($type, array $tokens, array $data = array(), array $options = array(), BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();
  // Current page tokens.
  if ($type == 'current-page') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'page-number-text':
          if ($page = \Drupal::request()->query->get('page')) {
            // @see PagerDefault::execute()
            $pager_page_array = explode(',', $page);
            $page = $pager_page_array[0];
            $page = (int)$page + 1;
            $replacements[$original] = '- Page ' . $page;
          }
          else {
            $replacements[$original] = '';
          }
          break;
      }
    }
  }
  return $replacements;
}

/**
 * Implements hook_tokens_alter().
 */
function mtc_core_tokens_alter(array &$replacements, array $context, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  if($context['type'] == 'node') {
    if (isset($replacements['[node:field_methode]'])) {
      $description = preg_replace("/(\r?\n)|(\t)|(^\s)/", "", strip_tags($replacements['[node:field_methode]']->__toString()));
      $replacements['[node:field_methode]'] = Unicode::truncate($description, 160, TRUE, TRUE);
    }
  }
}