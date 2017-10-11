<?php

/**
 * @file
 * Contains \Drupal\mtc_core\TaxonomyManager.
 */
namespace Drupal\mtc_core\Manager;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Form\FormBuilder;

/**
 * Class TaxonomyManager.
 *
 * @package Drupal\mtc_core
 */
class TaxonomyManager implements TaxonomyManagerInterface {

    /**
     * Drupal\Core\Database\Driver\mysql\Connection definition.
     *
     * @var \Drupal\Core\Database\Driver\mysql\Connection
     */
    protected $database;

    /**
     * Drupal\Core\Entity\EntityManager definition.
     *
     * @var Drupal\Core\Entity\EntityManager
     */
    protected $entity_manager;

    /**
     * Drupal\Core\Entity\Query\QueryFactory definition.
     *
     * @var Drupal\Core\Entity\Query\QueryFactory
     */
    protected $entity_query;

    /*
     * Default block id to newsletter block
     * @var String
     */
    protected $defaultBlockNewsletter = 'lcsubscriptionnewsletterblock';

    /*
     * Default Number of items per page
     * @var Interger
     */
    const NUM_PER_PAGE = 15;

    /**
     * Constructor.
     */
    public function __construct(Connection $database, EntityManager $entity_manager, QueryFactory $entity_query)
    {
        $this->database = $database;
        $this->entity_manager = $entity_manager;
        $this->entity_query = $entity_query;
    }

    /*
     * Get promoted field data of term
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getTermFieldsData(Term $term)
    {
        $fields = $term->getFields();
        // obtain fields
        $relatedArticles = isset($fields['field_article_liee']) ? $fields['field_article_liee']->getValue() : [];
        $introText = isset($fields['field_chapeau']) ? $fields['field_chapeau']->getValue() : NULL;
        $metaDescription = isset($fields['field_meta_description']) ? $fields['field_meta_description']->getValue() : NULL;
        $pageColor = isset($fields['couleur']) ? $fields['couleur']->getValue() : NULL;
        $h1Title = isset($fields['field_page_h1_title']) ? $fields['field_page_h1_title']->getValue() : NULL;
        $pageTitle = isset($fields['field_page_title']) ? $fields['field_page_title']->getValue() : NULL;
        $showCaseNid = isset($fields['field_theme_a_l_une']) ? $fields['field_theme_a_l_une']->getValue() : NULL;
        $showCaseNode = empty($showCaseNid) ? NULL : \Drupal\Node\Entity\Node::load($showCaseNid[0]['target_id']);
        // for theme dossier
        $parentTermTids = isset($fields['field_parent_theme']) ? $fields['field_parent_theme']->getValue() : NULL;
        $parentTerms = [];
        if (is_array($parentTermTids)) {
            foreach ($parentTermTids as $parentTid) {
                $parentTerms[] = $parentTid['target_id'];
            }
        }
        $parentTerms = empty($parentTerms) ? NULL : \Drupal\taxonomy\Entity\Term::loadMultiple($parentTerms);
        // obtain related articles nodes
        $relatedArtNids = [];
        foreach ($relatedArticles as $articleNid) {
            $relatedArtNids[] = $articleNid['target_id'];
        }
        $relatedArticles = empty($relatedArtNids) ? false : \Drupal\node\Entity\Node::loadMultiple($relatedArtNids);
        return compact('relatedArticles', 'introText', 'metaDescription', 'pageColor', 'h1Title', 'pageTitle', 'showCaseNode', 'parentTerms');
    }

    /*
     * Get the content based on vocubalary
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getContent(Term $term)
    {
        $content = NULL;
        $vocubalary = $term->getVocabularyId();
        // case if abc regimes
        $tidAbcRegime = theme_get_setting('abc_regime_tid') ?? false;
        if ($term->id() == $tidAbcRegime && $tidAbcRegime && $vocubalary == 'theme') {
            return $this->getArticlesThemeAbc($term);
        }

        switch ($vocubalary) {
            case 'theme':
                $content = $this->getArticlesTheme($term);
                break;
            case 'tag_dossier':
                $content = $this->getArticlesDossier($term);
                break;
            case 'tag_transverse_contenu':
                $content = $this->getArticlesTransvervse($term);
                break;
            case 'tag_transverse_format':
                $content = $this->getArticlesTransvervse($term);
                break;
            default:
                break;
        }
        return $content;
    }

    /*
     * Get the children of theme and articles
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getArticlesTheme(Term $mainTerm)
    {
        $content = [];
        // list ofArtcile nids found in children of mainTerm,used of exclusion
        $listOfNids = array(
            'O'
        );
        $vocubalary = $mainTerm->getVocabularyId();
        $tid = $mainTerm->id();
        $term_storage = $this->entity_manager->getStorage('taxonomy_term');
        // obtain taxonomy children hierarachy
        $childTerms = $term_storage->loadTree($vocubalary, $tid, 1, true);
        foreach ($childTerms as $term) {
            $content['child'][$term->id()]['termFields'] = $this->getTermFieldsData($term);
            $content['child'][$term->id()]['term'] = $term;
            $content['child'][$term->id()]['termDossier'] = $this->getTermListDossier($term);
            $content['child'][$term->id()]['subChildren'] = $term_storage->loadTree($vocubalary, $term->id(), 1, true);
            // limit to 6 articles if sub children
            $content['child'][$term->id()]['recentArticles'] = $this->getArticlesOfTerm($term, 6);

            $nids = is_array($content['child'][$term->id()]['recentArticles']) ? array_keys($content['child'][$term->id()]['recentArticles']) : [];
            $listOfNids = array_merge($listOfNids, $nids);
        }
        $page = pager_find_page() ?? 0;
        $page = ($page == 1) ? 0 : $page;
        $offset = $page * self::NUM_PER_PAGE;
        $offset = $offset ?? 0;
        $content['mainTerm']['recentArticles'] = $this->getArticlesOfTerm($mainTerm, self::NUM_PER_PAGE, $offset, $listOfNids);
        $content['mainTerm']['termFields'] = $this->getTermFieldsData($mainTerm);
        $content['mainTerm']['recentArticlescount'] = $this->getArticlesOfTermCount($mainTerm, $listOfNids);
        $content['mainTerm']['defaultTitle'] = $mainTerm->getName();
        $content['mainTerm']['termDossier'] = $this->getTermListDossier($mainTerm);
        // level 1 root => le mag'
        $content['mainTerm']['depth'] = $this->getTermDepth($mainTerm);
        $content['mainTerm']['relatedArticles'] = $this->getRelatedArticles($mainTerm);
        $content['mainTerm']['tid'] = $tid;

        // load newsletter block
        $block = \Drupal\block\Entity\Block::load($this->defaultBlockNewsletter);
        if (! empty($block)) {
            $block_content = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
            $content['newsletter'] = drupal_render($block_content);
        }
        // add pager
        $totalCount = $this->getArticlesOfTermCount($mainTerm, ['0']);
        pager_default_initialize($totalCount, self::NUM_PER_PAGE);
        $renderPager = [];
        $renderPager[] = [
            '#type' => 'pager'
        ];
        $content['pager'] = render($renderPager);
        return $content;
    }

    /*
     * Get the children of theme and articles
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getArticlesThemeAbc(Term $mainTerm)
    {
        $content = [];
        // list ofArtcile nids found in children of mainTerm,used of exclusion
        $listOfNids = array(
            'O'
        );
        $vocubalary = $mainTerm->getVocabularyId();
        $tid = $mainTerm->id();
        $firstLetterList = [];
        $term_storage = $this->entity_manager->getStorage('taxonomy_term');
        // obtain taxonomy children hierarchy
        $childTerms = $term_storage->loadTree($vocubalary, $tid, 1, true);
        foreach ($childTerms as $term) {
            $content['child'][$term->id()]['term'] = $term;
            // limit to 10 articles of sub children
            $content['child'][$term->id()]['recentArticles'] = $this->getArticlesOfTerm($term, 10);
            $content['child'][$term->id()]['name'] = $term->getName();
            $firstLetter = substr($term->getName(), 0, 1);
            if (! in_array($firstLetter, $firstLetterList)) {
                $content['child'][$term->id()]['firstLetter'] = $firstLetter;
                $firstLetterList[] = $firstLetter;
            }
            $nids = is_array($content['child'][$term->id()]['recentArticles']) ? array_keys($content['child'][$term->id()]['recentArticles']) : [];
            $listOfNids = array_merge($listOfNids, $nids);
        }
        $childTerms = $content['child'];
        // order by name
        usort($childTerms, function ($a, $b) {
            return $a['name'] <=> $b['name'];
        });
        $content['child'] = $childTerms;
        $content['mainTerm']['termFields'] = $this->getTermFieldsData($mainTerm);
        $content['mainTerm']['defaultTitle'] = $mainTerm->getName();
        $content['mainTerm']['termDossier'] = $this->getTermListDossier($mainTerm);
        // level 1 root => le mag'
        $content['mainTerm']['depth'] = $this->getTermDepth($mainTerm);
        $content['mainTerm']['tid'] = $tid;
        // load newsletter block
        $block = \Drupal\block\Entity\Block::load($this->defaultBlockNewsletter);
        if (! empty($block)) {
            $block_content = \Drupal::entityTypeManager()->getViewBuilder('block')->view($block);
            $content['newsletter'] = drupal_render($block_content);
        }
        return $content;
    }

    /*
     * Get the termfields & articles of tag dossier
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getArticlesDossier(Term $mainTerm)
    {
        $content = [];
        // list ofArtcile nids found in children of mainTerm,used of exclusion
        $excludeNids = [
            'O'
        ];
        // obtain articles and term fields
        $page = pager_find_page();
        $page = ($page == 1) ?  0 : $page ;
        $offset = $page * self::NUM_PER_PAGE;
        $offset = $offset ?? 0;
        $content['mainTerm']['articles'] = $this->getArticlesOfTerm($mainTerm, self::NUM_PER_PAGE, $offset, $excludeNids);
        $totalCount = $this->getArticlesOfTermCount($mainTerm, $excludeNids);
        $content['mainTerm']['termFields'] = $this->getTermFieldsData($mainTerm);
        $content['mainTerm']['title'] = $mainTerm->getName();

        // Now that we have the total number of results, initialize the pager.
        pager_default_initialize($totalCount, self::NUM_PER_PAGE);
        $render = [];
        // Finally, add the pager to the render array, and return.
        $render[] = [
            '#type' => 'pager'
        ];
        $content['mainTerm']['pager'] = render($render);

        $content['child'] = [];
        // get artcicles of main of parent term found inf field
        $parentTerm = array_shift($content['mainTerm']['termFields']['parentTerms']);
        if (empty($parentTerm))
            return $content;
        $content['parentTerm']['term'] = $parentTerm;
        $content['parentTerm']['termDossier'] = $this->getTermListDossier($parentTerm);
        $content['parentTerm']['articles'] = $this->getArticlesOfTerm($parentTerm, $limit = 2, $offset = 0, $excludeNids);
        return $content;
    }

    /*
     * Get the articles & field data of tag transverse
     * @param \Drupal\taxonomy\Entity\Term $term
     * The taxonomy term.
     * @return Array
     */
    public function getArticlesTransvervse(Term $mainTerm)
    {
        $content = [];
        $content['mainTerm']['termFields'] = $this->getTermFieldsData($mainTerm);
        $content['mainTerm']['articles'] = [];
        $content['mainTerm']['term'] = $mainTerm;
        $content['child'] = $this->getArticlesOfTermTransverse($mainTerm);
        return $content;
    }

    /*
     * Get all recent articles related to a term
     * @param \Drupal\taxonomy\Entity\Term $term
     * @param int $limit
     * @param int $offset
     * @param Array $excludeNids
     * @param Array $includeNodeType
     *
     * return \Drupal\node\Entity\Node
     *
     */
    public function getArticlesOfTerm(Term $term, $limit = 4, $offset = 0, $excludeNids = array('0'), $includeNodeTypes = false)
    {
        $tid = $term->id();
        $query = $this->database->select('taxonomy_index', 'ti');
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = ti.nid');
        $query->fields('ti', [
            'nid'
        ]);
        $query->condition('ti.tid', $tid);
        $query->condition('ti.status', 1);
        $query->condition('nfd.status', 1);
        $query->condition('nfd.nid', $excludeNids, 'NOT IN');
        if ($includeNodeTypes) {
            $query->condition('nfd.type', $includeNodeTypes, 'IN');
        }
        $query->orderby('nfd.changed', 'DESC');
        $query->range($offset, $limit);
        $nids = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($nids);
        return empty($nids) ? false : \Drupal\node\Entity\Node::loadMultiple($nids);
    }

    /*
     * Get all random articles od same theme having tag transverse
     * @param \Drupal\taxonomy\Entity\Term $term
     * @param int $limit
     * @param int $offset
     * @param Array $excludeNids
     * @param Array $includeNodeType
     *
     * @return \Drupal\node\Entity\Node
     *
     */
    public function getRandomArticlesTransverse(Term $term, $limit = 5, $offset = 0, $excludeNids = array('0'), $includeNodeTypes = false)
    {
        $tid = $term->id();
        $query = $this->database->select('taxonomy_index', 'ti');
        $query->leftJoin('taxonomy_index', 'ti2', 'ti2.nid = ti.nid');
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = ti2.nid');
        $query->leftJoin('taxonomy_term_data', 'ttd', 'ttd.tid = ti2.tid');

        $query->fields('ti2', [
            'nid'
        ]);
        $query->condition('ti.status', 1);
        $query->condition('ti.tid', $tid);
        $query->condition('nfd.status', 1);
        $query->condition('nfd.nid', $excludeNids, 'NOT IN');
        $query->condition('ttd.vid', 'tag_transverse_contenu');
        if ($includeNodeTypes) {
            $query->condition('nfd.type', $includeNodeTypes, 'IN');
        }
        $query->orderRandom();
        $query->range($offset, $limit);
        $nids = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($nids);
        return empty($nids) ? false : \Drupal\node\Entity\Node::loadMultiple($nids);
    }

    /*
     * Get all random articles
     * @param \Drupal\taxonomy\Entity\Term $term
     * @param int $limit
     * @param int $offset
     * @param Array $excludeNids
     * @param Array $includeNodeType
     *
     * @return \Drupal\node\Entity\Node
     *
     */
    public function getRandomArticlesOfTerm($limit = 4, $offset = 0, $excludeNids = array('0'), $includeNodeTypes = false)
    {
        $query = $this->database->select('taxonomy_index', 'ti');
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = ti.nid');
        $query->fields('ti', [
            'nid'
        ]);
        $query->condition('ti.status', 1);
        $query->condition('nfd.status', 1);
        $query->condition('nfd.nid', $excludeNids, 'NOT IN');
        if ($includeNodeTypes) {
            $query->condition('nfd.type', $includeNodeTypes, 'IN');
        }
        $query->orderRandom();
        $query->range($offset, $limit);
        $nids = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($nids);
        return empty($nids) ? false : \Drupal\node\Entity\Node::loadMultiple($nids);
    }

    /*
     * Get count of articles related to a term
     * @param \Drupal\taxonomy\Entity\Term $term
     * @param Array $excludeNids nids to exclude
     *
     * @return \Drupal\node\Entity\Node
     *
     */
    public function getArticlesOfTermCount(Term $term, $excludeNids = array('0'))
    {
        $vocubalary = $term->getVocabularyId();
        $tid = $term->id();
        $query = $this->database->select('taxonomy_index', 'ti');
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = ti.nid');
        $query->fields('ti', [
            'nid'
        ]);
        $query->condition('ti.tid', $tid);
        $query->condition('ti.status', 1);
        $query->condition('nfd.status', 1);
        $query->condition('nfd.nid', $excludeNids, 'NOT IN');
        $query->orderby('nfd.changed', 'DESC');
        $nids = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($nids);
        return count($nids);
    }

    /*
     * Get articles related to a term transverse
     * @param \Drupal\taxonomy\Entity\Term $term
     *
     * @return Array $content
     *
     */
    public function getArticlesOfTermTransverse(Term $term)
    {
        $tid = $term->id();
        $result = [];
        $content = [];
        // list of articles with the same term transverse and parent themes of the article
        // parent tid of nid : parent_1 => parent, parent_2 => grandparent, 3 => greatgrandParent...
        $articleNids = $this->database->query("
                          SELECT ti2.nid     AS nid,
                                 ti2.tid     AS parent_0,
                                 tth.parent  AS parent_1,
                                 tth2.parent AS parent_2,
                                 tth3.parent AS parent_3,
                                 tth4.parent AS parent_4,
                                 tth5.parent AS parent_5
                            FROM taxonomy_index ti2
                            LEFT JOIN taxonomy_term_data ttd       ON ttd.tid = ti2.tid
                            LEFT JOIN taxonomy_term_hierarchy tth  ON tth.tid = ti2.tid
                            LEFT JOIN taxonomy_term_hierarchy tth2 ON tth2.tid = tth.parent
                            LEFT JOIN taxonomy_term_hierarchy tth3 ON tth3.tid = tth2.parent
                            LEFT JOIN taxonomy_term_hierarchy tth4 ON tth4.tid = tth3.parent
                            LEFT JOIN taxonomy_term_hierarchy tth5 ON tth5.tid = tth4.parent
                            WHERE ttd.vid = 'theme'
                              AND ti2.status = 1
                              AND ti2.nid IN
                                (SELECT DISTINCT ti.nid
                                 FROM taxonomy_index ti
                                 WHERE ti.tid = :tid
                                   AND ti.status = 1)", array(
            ':tid' => $tid
        ))->fetchAllAssoc('nid', \PDO::FETCH_ASSOC);

        foreach ($articleNids as $article) {
            // search for root hierarchy
            if ($article['parent_3'] === "0") {
                // regroup only by level 2
                $result[$article['parent_1']]['nids'][] = $article['nid'];
                $result[$article['parent_1']]['subtheme'][] = $article['parent_0'];
            }
            if ($article['parent_4'] === "0") {
                // regroup only by level 2
                $result[$article['parent_2']]['nids'][] = $article['nid'];
                $result[$article['parent_2']]['subtheme'][] = $article['parent_1'];
            }
            if ($article['parent_5'] === "0") {
                // regroup only by level 2
                $result[$article['parent_3']]['nids'][] = $article['nid'];
                $result[$article['parent_3']]['subtheme'][] = $article['parent_2'];
            }
        }
        // load entities
        foreach ($result as $tid => $res) {
            $content[$tid]['term'] = $this->entity_manager->getStorage('taxonomy_term')->load($tid);
            $content[$tid]['articles'] = \Drupal\node\Entity\Node::loadMultiple($res['nids']);
            $content[$tid]['termFields'] = $this->getTermFieldsData($content[$tid]['term']);
            $content[$tid]['subthemes'] = \Drupal\taxonomy\Entity\Term::loadMultiple($res['subtheme']);
            // get weight of term
            $weightResult = db_select('taxonomy_term_field_data', 'ttfd')->condition('ttfd.tid', $tid)
                ->fields('ttfd', array(
                'weight'
            ))
                ->execute()
                ->fetch();
            $content[$tid]['weight'] = $weightResult->weight;
        }
        usort($content, function ($a, $b) {
            return $a['weight'] <=> $b['weight'];
        });
        return $content;
    }

    /**
     * Function that obtains the list terms dossier (tags) associated with term theme
     *
     * @param \Drupal\taxonomy\Entity\Term $term
     * @return Array \Drupal\taxonomy\Entity\Term $term
     */
    public function getTermListDossier(Term $term)
    {
        $tid = $term->id();
        $tids = $this->database->query("
                        SELECT  ttfpt.entity_id AS tid
                        FROM taxonomy_term__field_parent_theme ttfpt
                        WHERE ttfpt.field_parent_theme_target_id = :tid
                       ", array(
            ':tid' => $tid
        ))->fetchAllAssoc('tid', \PDO::FETCH_ASSOC);

        return \Drupal\taxonomy\Entity\Term::loadMultiple(array_keys($tids));
    }

    /**
     * Function obtains related articles of a term.
     *
     * @param \Drupal\taxonomy\Entity\Term $term
     * @return Array of \Drupal\node\Entity\Node
     */
    public function getRelatedArticles(Term $term)
    {
        $tid = $term->id();
        $nids = $this->database->query("
                        SELECT field_article_liee_target_id
                        FROM taxonomy_term__field_article_liee ttfal
                        WHERE ttfal.entity_id = :tid
                       ", array(
            ':tid' => $tid
        ))->fetchAllAssoc('field_article_liee_target_id', \PDO::FETCH_ASSOC);
        return empty($nids) ? [] : \Drupal\node\Entity\Node::loadMultiple(array_keys($nids));
    }

    /**
     * Function obtains get Term Depth.
     *
     * @param \Drupal\taxonomy\Entity\Term $term
     * @return int
     */
    public function getTermDepth(Term $term)
    {
        $limit = 4;
        $depth = 0;
        $tid = $term->id();
        while ($parent = $this->database->query(" SELECT parent FROM taxonomy_term_hierarchy tth WHERE tth.tid = :tid", array(
            ':tid' => $tid
        ))->fetchField()) {
            ;
            $depth ++;
            $tid = $parent;
            if ($depth > $limit) {
                break;
            }
        }
        return $depth;
    }
}
