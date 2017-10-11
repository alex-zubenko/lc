<?php

/**
 * @file
 * Contains \Drupal\mtc_core\TaxonomyManagerInterface.
 */

namespace Drupal\mtc_core\Manager;
use Drupal\taxonomy\Entity\Term;

/**
 * Interface TaxonomyManagerInterface.
 *
 * @package Drupal\mtc_core
 */
interface TaxonomyManagerInterface {

    /**
     * Get the data of taxonomy found in fields
     * @param \Drupal\taxonomy\Entity\Term $term
     *   The taxonomy term.
     *
     * @return Array
     */
    public function getTermFieldsData(Term $term);


}
