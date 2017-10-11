<?php
namespace Drupal\mtc_core\TwigExtension;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\mtc_core\Utils\TaxonomyUtility;
use Drupal\Core\Template\TwigExtension;
use Drupal\Core\Url;

/**
 * A test Twig extension that adds a custom function and a custom filter.
 */
class LinecoachingDavidTwigExtension extends TwigExtension
{

    /**
     * Generates a list of all Twig functions that this extension defines.
     *
     * @return array A key/value array that defines custom Twig functions. The key denotes the
     *         function name used in the tag, e.g.:
     *         @code
     *         {{ testfunc() }}
     *         @endcode
     *
     *         The value is a standard PHP callback that defines what the function does.
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_forum_details', [
                $this,
                'getForumDetails'
            ], [
                'is_safe' => [
                    'html'
                ]
            ])
        ];
    }

    /**
     * Generates a list of all Twig filters that this extension defines.
     *
     * @return array A key/value array that defines custom Twig filters. The key denotes the
     *         filter name used in the tag, e.g.:
     *         @code
     *         {{ foo|truncate }}
     *         @endcode
     *
     *         The value is a standard PHP callback that defines what the filter does.
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('get_forum_visibility', [
                $this,
                'getForumVisibility'
            ]),
            new \Twig_SimpleFilter('twig_load_block', [
                $this,
                'twigLoadBlock'
            ])
        ];
    }

    /**
     * Function that load a block inside twig template.
     *
     * @param string $bid
     * @return string $block_loaded or false
     */
    public function twigLoadBlock($bid, $alternate_bid = null)
    {
        $userCurrent = \Drupal::currentUser();
        $block_loaded = false;
        $block = false;
        if ($userCurrent->isAnonymous()) {
            // block for anonymous user
            $block = \Drupal\block\Entity\Block::load($bid);
        } elseif ($alternate_bid) {
            // block for logged in user
            $block = \Drupal\block\Entity\Block::load($alternate_bid);
        } else {}
        if ($block) {
            $block_loaded = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
        }
        return $block_loaded;
    }

    /**
     * Function that obtains user picto from uid or title.
     *
     * @param int $nid
     * @param
     *            string fieldImage
     * @return string $fieldImage
     */
    // @todo uid
    public function getForumVisibility($forum)
    {
        $clean_roles = true;
        if ($forum) {
            $account = \Drupal::currentUser()->getRoles();
            if ($tid = $forum->tid->value) {
                $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($tid);
                $dd = array();
                foreach ($parents as $key => $value) {
                    if (! $this->checkVisibility($value, $account)) {
                        $clean_roles = false;
                    }
                }
            }
            if ($clean_roles) {
                if (! $this->checkVisibility($forum, $account)) {
                    $clean_roles = false;
                }
            }
            return $clean_roles;
        }
    }

    /**
     * Function getForumDetails
     *
     * @param int $forum
     */
    // @todo uid
    public function getForumDetails($forum)
    {
        // static $passed;
        // if(!$passed) {
        if ($forum) {
            $account = \Drupal::currentUser();
            $toto = 'coucou details';
            $passed = true;
            return 'details<pre>' . print_r($toto, true) . '</pre>';
        } else {
            return '<pre>no forum details</pre>';
        }
        // }
        // else {
        // return 'Passed details';
        // }
    }

    /**
     * Gets a unique identifier for this Twig extension.
     *
     * @return string A unique identifier for this Twig extension.
     */
    public function getName()
    {
        return 'linecoachingdavid.twig_extension';
    }

    private function checkVisibility($forum_term, $account_roles)
    {
        $clean_roles = true;
        if (count($forum_term->field_forum_visibility) > 0) {
            if (in_array('administrator', $account_roles)) {
                return true;
            }
            $clean_roles = false;
            foreach ($forum_term->field_forum_visibility->getIterator() as $key => $value) {
                if (in_array($value->value, $account_roles)) {
                    $clean_roles = true;
                }
            }
        }
        return $clean_roles;
    }
}
