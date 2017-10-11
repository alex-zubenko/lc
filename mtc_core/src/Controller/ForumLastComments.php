<?php

namespace Drupal\mtc_core\Controller;

use Drupal\forum\Controller\ForumController;
use Drupal\taxonomy\TermInterface;

class ForumLastComments extends ForumController {

  /**
   * @inheritdoc
   */
  public function forumPage(TermInterface $taxonomy_term) {
    // Get forum details.
    $taxonomy_term->forums = $this->forumManager->getChildren($this->config('forum.settings')->get('vocabulary'), $taxonomy_term->id());
    $taxonomy_term->parents = $this->forumManager->getParents($taxonomy_term->id());

    if (empty($taxonomy_term->forum_container->value)) {
      $build = $this->forumManager->getTopics($taxonomy_term->id(), $this->currentUser());
      $topics = $build['topics'];
      $header = $build['header'];
    }
    else {
      $topics = [];
      $header = [];
    }
    $foo = &drupal_static(__TOPIC__, $build['topics']);
    return $this->build($taxonomy_term->forums, $taxonomy_term, $topics, $taxonomy_term->parents, $header);
  }

}
