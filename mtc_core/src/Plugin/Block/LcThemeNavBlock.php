<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\TermStorage;
use Drupal\Core\Url;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'ThemeNavBlock' block.
 * Left Menu for theme vocabulary
 *
 * @Block(
 * id = "lc_theme_nav_block",
 * admin_label = @Translation("mtc_core theme/taxonomy navigation menu block"),
 * category = @Translation("Mtc Core Custom block")
 * )
 */
class LcThemeNavBlock extends BlockBase {

    const ALLOWEDTHEMES = [
        'tag_transverse_contenu',
        'tag_transverse_format',
        'theme',
        'tag_dossier'
    ];

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form['level'] = [
            '#type' => 'number',
            '#title' => $this->t('Level'),
            '#description' => $this->t(''),
            '#default_value' => isset($this->configuration['level']) ? $this->configuration['level'] : 2,
            '#weight' => '3'
        ];
        return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        $this->configuration['level'] = $form_state->getValue('level');
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('level') < 1 || $form_state->getValue('level') > 5) {
            $form_state->setErrorByName('level', $this->t('Please enter a number between 1 and 5'));
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build = [];
        $items = [];
        $childItemTid = [];
        $loop = 0;
        $vocId = [];
        $currentPath = \Drupal::request()->getRequestUri();
        // checks used to remove block from non-mag pages
        $node = \Drupal::routeMatch()->getParameter('node');
        $taxonomyTerm = \Drupal::routeMatch()->getParameter('taxonomy_term');
        $isNodeTheme = isset($node) ? $node->hasField('field_theme') : false;
        $isMagTheme = isset($taxonomyTerm) ? in_array($taxonomyTerm->getVocabularyId(), self::ALLOWEDTHEMES) : false;
        if (! $isNodeTheme && ! $isMagTheme) {
            // return empty (not in le mag)
            return $build['items'] = [];
        }

        if ($node && property_exists($node, 'field_theme_prioritaire')) {
            $value = $node->get('field_theme_prioritaire')->getValue();
            if (! empty($value[0]['target_id'])) {
                $themePrioritaireTid = $value[0]['target_id'];
                $url = Url::fromRoute('entity.taxonomy_term.canonical', [
                    'taxonomy_term' => $themePrioritaireTid
                ]);
                $currentPath = $url->toString();
            }
        }
        // obtain themes upto form level value, default:2
        $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $level = isset($this->configuration['level']) ? (int) $this->configuration['level'] : 2;
        $themeList = $termStorage->loadTree('theme', 0, 1, false);
        // remove case of 'Le mag' / remove root
        if (count($themeList) == 1) {
            $parentTid = $themeList[0]->tid;
            $themeList = $termStorage->loadTree('theme', $parentTid, 1, false);
        }
        $level --;
        foreach ($themeList as $theme) {
            $items[$theme->tid] = $this->generateHtml($theme, $currentPath);
            // obtain children
            $items[$theme->tid]['below'] = $this->populateTree($theme->tid, $level, $currentPath);
        }

        // find and create tree to active menu
        $treeToActiveItem = $this->findActiveTrailItem($items, []);
        // modify css classes for active trail
        $this->activateTrailMenu($items, $treeToActiveItem);
        $build['items'] = $items;
        return $build;
    }

    /*
     * Function that obtains active tree trail css
     * @param array $items tree content structure
     * @param array $treeArray tree route structe to active element
     * @return array route
     */
    public function findActiveTrailItem($items, $treeArray = [])
    {
        $result = false;
        if (empty($items)) {
            return $result;
        }
        foreach ($items as $item) {
            $tree = $treeArray;
            $tree[] = $item['tid'];
            if ($item['in_active_trail']) {
                $result = $tree;
            } else {
                $result = $this->findActiveTrailItem($item['below'], $tree);
            }
            if ($result) {
                return $result;
            }
        }
    }

    /*
     * Function that modifies css active trail
     * @param array $items tree content structure
     * @param array $treeArray tree route structe to active element
     */
    public function activateTrailMenu(&$items, $treeArray = [])
    {
        if (empty($treeArray))
            return;
        $tid = array_shift($treeArray);
        $items[$tid]['in_active_trail'] = true;
        $items[$tid]['is_collapsed'] = null;
        $items[$tid]['is_expanded'] = true;
        if (count($items[$tid]['below']) > 1)
            $this->activateTrailMenu($items[$tid]['below'], $treeArray);
    }

    /**
     * Function that populates Tree recusively
     *
     * @param int $tid
     *            taxonomy Tid
     * @param int $depth
     *            depth of Tree
     * @param
     *            string currentPath
     * @return array
     */
    public function populateTree($tid, $depth, $currentPath)
    {
        if ($depth < 0)
            return;
        $result = [];
        $termStorage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
        $depth --;
        $themeList = $termStorage->loadTree('theme', $tid, 1, false);
        foreach ($themeList as $theme) {
            $result[$theme->tid] = $this->generateHtml($theme, $currentPath, $depth);
            // obtain children
            $result[$theme->tid]['below'] = $this->populateTree($theme->tid, $depth, $currentPath);
        }
        return $result;
    }

    /**
     * Function that generates the html content;
     *
     * @param $theme Drupal\taxonomy\Term
     * @param $curentPath String
     * @param
     *            shallow Int (opposite of detpth => 0 zero depth)
     * @return array
     */
    public function generateHtml($theme, $currentPath = NULL, $shallow = NULL)
    {
        $in_active_trail = null;
        $is_collapsed = true;
        $is_expanded = null;
        $below = false;
        $last_level = false;
        $attributes = new Attribute();
        $tid = $theme->tid;
        $url = Url::fromRoute('entity.taxonomy_term.canonical', array(
            'taxonomy_term' => $tid
        ), [
            'attributes' => [
                'title' => $theme->name
            ]
        ]);
        $title = $theme->name;

        if ($url->toString() == $currentPath) {
            $in_active_trail = true;
            $is_collapsed = null;
            $is_expanded = true;
        }
        if ($shallow < 0) {
            $last_level = true;
        }
        return compact('in_active_trail', 'below', 'attributes', 'is_collapsed', 'is_expanded', 'url', 'title', 'tid', 'last_level');
    }

    public function getCacheTags()
    {
        $node = \Drupal::routeMatch()->getParameter('node');
        $taxonomyTerm = \Drupal::routeMatch()->getParameter('taxonomy_term');
        $isNodeTheme = isset($node) ? $node->hasField('field_theme') : 0;
        $isMagTheme = isset($taxonomyTerm) ? in_array($taxonomyTerm->getVocabularyId(), self::ALLOWEDTHEMES) : 0;
        // Return default tags instead.
        // var_dump($isNodeTheme.$isMagTheme);
        return Cache::mergeTags(parent::getCacheTags(), [
            'nodeThemeMag:' . $isNodeTheme . $isMagTheme
        ]);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getCacheContexts()
    {
        return Cache::mergeContexts(parent::getCacheContexts(), [
            'url.path'
        ]);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @todo Make cacheable in https://www.drupal.org/node/2483181
     */
    public function getCacheMaxAge()
    {
        return 0;
    }
}
