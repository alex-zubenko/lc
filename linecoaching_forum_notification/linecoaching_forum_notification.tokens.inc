<?php

/**
 * @file
 *   Provides tokens for the Notification module
 */

use Drupal\Core\Render\Markup;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info_alter().
 */
function linecoaching_forum_notification_token_info_alter(&$data) {

  // Define private message thread additional tokens
  $data['tokens']['user']['user-subscribed-posts'] = [
    'name' => t('User subscribed posts'),
    'description' => t('User subscribed posts'),
    'type' => 'text',
  ];
}

/**
 * Implements hook_tokens().
 */
function linecoaching_forum_notification_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  // Replace private message tokens
  if ($type == 'user' && !empty($data['user'])) {

    $account = $data['user'];
    $db = \Drupal::database();

    $query = $db->select('lc_forum_notification', 'fn');
    $query->fields('fn', ['nid']);
    $query->condition('fn.status', 1);
    $query->condition('fn.uid', $account->id());
    $total = $query->execute()->fetchCol();
    if ($total) {
      $nodes = Node::loadMultiple($total);
      $links = [];
      /** @var Drupal\node\Entity\Node $node */
      foreach ($nodes as $node) {
        $links[] = $node->toLink($node->getTitle(),$rel = 'canonical',['absolute' => TRUE])->toString();
      }
      $list = implode("</br>\r\n", $links);

      foreach ($tokens as $name => $original) {
        switch ($name) {
          // Basic user account information.
          case 'user-subscribed-posts':
            // In the case of hook user_presave uid is not set yet.
            $replacements[$original] = Markup::create($list);
            break;
        }
      }
    }
  }

  return $replacements;
}
