<?php

/**
 * @file
 * Contains \Drupal\mtc_core\Utils.
 */

namespace Drupal\mtc_core\Utils;
use Drupal\taxonomy\Entity\Term;

/**
 * Class TaxonomyUtility.
 *
 * @package Drupal\mtc_core
 */
class TaxonomyUtility{

  /**
   * Function that converts à term value field to taxonomy
   * @param array
   * @return   \Drupal\taxonomy\Entity\Term $term
   */
    public static function taxonomyNodeFieldToTerm($taxonomyArray){
        if(!isset($taxonomyArray[0]['target_id'])) return [];
        $termTid = $taxonomyArray[0]['target_id'];
        return Term::load($termTid);
    }

    /*
     * Function that obtains parent term of term at certain level
     * @param   int $tid
     * @param int $level
     * @return \Drupal\taxonomy\Entity\Term $term
     */
    public static function getParentTerm($tid, $level = null){
      if(empty($level)) return $tid;
      $depth  = self::termDepth($tid);
      $parent = $tid;
      while($depth != $level && !empty($parent) && $depth > 0) {
          $parent = db_query("SELECT parent FROM {taxonomy_term_hierarchy} WHERE tid = :tid", array(':tid' => $tid))->fetchField();
          $tid = $parent;
          $depth--;
      }
      return $parent;

    }

    /*
     * Function that obtains depth of term
     * @param   int $tid
     * @return  \Drupal\taxonomy\Entity\Term $term
     */
    public static function termDepth($tid) {
        $parent = db_query("SELECT parent FROM {taxonomy_term_hierarchy} WHERE tid = :tid", array(':tid' => $tid))->fetchField();
        if(empty($parent)) {
            return 1;
        }else  {
            return 1 + self::termDepth($parent);
        }
    }

}