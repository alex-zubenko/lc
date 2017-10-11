<?php

namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Search form' block.
 *
 * @Block(
 *  id = "lc_search_form_block",
 *  admin_label = @Translation("mtc_core custom search block"),
 *  category = @Translation("MTC Core Custom block")
 * )
 */
class LcSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\mtc_core\Form\SearchBlockForm');
    //being a get method,remove noise
    unset($form['form_build_id']);
    unset($form['form_id']);
    unset($form['#form_id']);
    return $form;
  }


  public function getCacheTags() {
      //With this when your node change your block will rebuild
      if ($taxonomyTerm = \Drupal::routeMatch()->getParameter('taxonomy_term')) {
          //if there is node add its cachetag
          return Cache::mergeTags(parent::getCacheTags(), array('taxonomyTerm:' . $taxonomyTerm->getVocabularyId()));
      } else {
          //Return default tags instead.
          return parent::getCacheTags();
      }
  }

  public function getCacheContexts() {
      //if you depends on \Drupal::routeMatch()
      //you must set context of this block with 'route' context tag.
      //Every new route this block will rebuild
      return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

}
