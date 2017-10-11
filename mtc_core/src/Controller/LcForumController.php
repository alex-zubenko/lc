<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\mtc_core\Traits\ForumTrait;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\forum\ForumManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LcForumController.
 *
 * @package Drupal\mtc_core\Controller
 */
class LcForumController extends ControllerBase
{

    use ForumTrait;

    /**
     * Forum manager service.
     *
     * @var \Drupal\forum\ForumManagerInterface
     */
    protected $forumManager;

    /**
     * Vocabulary storage.
     *
     * @var \Drupal\taxonomy\VocabularyStorageInterface
     */
    protected $vocabularyStorage;

    /**
     * Term storage.
     *
     * @var \Drupal\taxonomy\TermStorageInterface
     */
    protected $termStorage;

    /**
     * Node access control handler.
     *
     * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
     */
    protected $nodeAccess;

    /**
     * Field map of existing fields on the site.
     *
     * @var array
     */
    protected $fieldMap;

    /**
     * Node type storage handler.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface
     */
    protected $nodeTypeStorage;

    /**
     * The renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * Node entity type, we need to get cache tags from here.
     *
     * @var \Drupal\Core\Entity\EntityTypeInterface
     */
    protected $nodeEntityTypeDefinition;

    /**
     * Comment entity type, we need to get cache tags from here.
     *
     * @var \Drupal\Core\Entity\EntityTypeInterface
     */
    protected $commentEntityTypeDefinition;

    /**
     * Constructs a ForumController object.
     *
     * @param \Drupal\forum\ForumManagerInterface $forum_manager
     *            The forum manager service.
     * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
     *            Vocabulary storage.
     * @param \Drupal\taxonomy\TermStorageInterface $term_storage
     *            Term storage.
     * @param \Drupal\Core\Session\AccountInterface $current_user
     *            The current logged in user.
     * @param \Drupal\Core\Entity\EntityAccessControlHandlerInterface $node_access
     *            Node access control handler.
     * @param array $field_map
     *            Array of active fields on the site.
     * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
     *            Node type storage handler.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *            The renderer.
     * @param \Drupal\Core\Entity\EntityTypeInterface $node_entity_type_definition
     *            Node entity type definition object
     * @param \Drupal\Core\Entity\EntityTypeInterface $comment_entity_type_definition
     *            Comment entity type definition object
     */
    public function __construct(ForumManagerInterface $forum_manager, VocabularyStorageInterface $vocabulary_storage, TermStorageInterface $term_storage, AccountInterface $current_user, EntityAccessControlHandlerInterface $node_access, array $field_map, EntityStorageInterface $node_type_storage, RendererInterface $renderer, EntityTypeInterface $node_entity_type_definition, EntityTypeInterface $comment_entity_type_definition)
    {
        $this->forumManager = $forum_manager;
        $this->vocabularyStorage = $vocabulary_storage;
        $this->termStorage = $term_storage;
        $this->currentUser = $current_user;
        $this->nodeAccess = $node_access;
        $this->fieldMap = $field_map;
        $this->nodeTypeStorage = $node_type_storage;
        $this->renderer = $renderer;
        $this->nodeEntityTypeDefinition = $node_entity_type_definition;
        $this->commentEntityTypeDefinition = $comment_entity_type_definition;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function create(ContainerInterface $container)
    {
        /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
        $entity_manager = $container->get('entity.manager');

        return new static($container->get('forum_manager'), $entity_manager->getStorage('taxonomy_vocabulary'), $entity_manager->getStorage('taxonomy_term'), $container->get('current_user'), $entity_manager->getAccessControlHandler('node'), $entity_manager->getFieldMap(), $entity_manager->getStorage('node_type'), $container->get('renderer'), $entity_manager->getDefinition('node'), $entity_manager->getDefinition('comment'));
    }

    /**
     *
     * @param \Drupal\taxonomy\TermInterface $taxonomy_term
     *
     * @return array A render array.
     */
    public function redirectTaxonomy(TermInterface $taxonomy_term)
    {
        $vid = $taxonomy_term->getVocabularyId();
        if ($vid == 'forums') {
            return $this->forumPage($taxonomy_term);
            // get forum manager
        } else {
            return entity_view($taxonomy_term, 'full');
        }
    }

    /**
     * Generates an action link to display at the top of the forum listing.
     *
     * @param string $vid
     *            Vocabulary ID.
     * @param \Drupal\taxonomy\TermInterface $forum_term
     *            The term for which the links are to be built.
     *
     * @return array Render array containing the links.
     */
    protected function buildActionLinks($vid, TermInterface $forum_term = NULL)
    {
        $user = $this->currentUser();

        $links = [];
        // Loop through all bundles for forum taxonomy vocabulary field.
        foreach ($this->fieldMap['node']['taxonomy_forums']['bundles'] as $type) {
            if ($this->nodeAccess->createAccess($type)) {
                $node_type = $this->nodeTypeStorage->load($type);
                $links[$type] = [
                    '#attributes' => [
                        'class' => [
                            'action-links'
                        ]
                    ],
                    '#theme' => 'menu_local_action',
                    '#link' => [
                        'title' => $this->t('Add new @node_type', [
                            '@node_type' => $this->nodeTypeStorage->load($type)
                                ->label()
                        ]),
                        'url' => Url::fromRoute('node.add', [
                            'node_type' => $type
                        ])
                    ],
                    '#cache' => [
                        'tags' => $node_type->getCacheTags()
                    ]
                ];
                if ($forum_term && $forum_term->bundle() == $vid) {
                    // We are viewing a forum term (specific forum), append the tid to
                    // the url.
                    $links[$type]['#link']['localized_options']['query']['forum_id'] = $forum_term->id();
                }
            }
        }
        if (empty($links)) {
            // Authenticated user does not have access to create new topics.
            if ($user->isAuthenticated()) {
                $links['disallowed'] = [
                    '#markup' => $this->t('You are not allowed to post new content in the forum.')
                ];
            }             // Anonymous user does not have access to create new topics.
            else {
                $links['login'] = [
                    '#attributes' => [
                        'class' => [
                            'action-links'
                        ]
                    ],
                    '#theme' => 'menu_local_action',
                    '#link' => array(
                        'title' => $this->t('Log in to post new content in the forum.'),
                        'url' => Url::fromRoute('user.login', [], [
                            'query' => $this->getDestinationArray()
                        ])
                    )
                ];
            }
        }
        return $links;
    }

    /**
     * Returns a renderable forum index page array.
     *
     * @param array $forums
     *            A list of forums.
     * @param \Drupal\taxonomy\TermInterface $term
     *            The taxonomy term of the forum.
     * @param array $topics
     *            The topics of this forum.
     * @param array $parents
     *            The parent forums in relation this forum.
     * @param array $header
     *            Array of header cells.
     *
     * @return array A render array.
     */
    protected function build($forums, TermInterface $term, $topics = array(), $parents = array(), $header = array())
    {
        $config = $this->config('forum.settings');
        $build = array(
            '#theme' => 'forums',
            '#forums' => $forums,
            '#topics' => $topics,
            '#parents' => $parents,
            '#header' => $header,
            '#term' => $term,
            '#sortby' => $config->get('topics.order'),
            '#forums_per_page' => $config->get('topics.page_limit')
        );
        if (empty($term->forum_container->value)) {
            $build['#attached']['feed'][] = array(
                'taxonomy/term/' . $term->id() . '/feed',
                'RSS - ' . $term->getName()
            );
        }
        $this->renderer->addCacheableDependency($build, $config);

        foreach ($forums as $forum) {
            $this->renderer->addCacheableDependency($build, $forum);
        }
        foreach ($topics as $topic) {
            $this->renderer->addCacheableDependency($build, $topic);
        }
        foreach ($parents as $parent) {
            $this->renderer->addCacheableDependency($build, $parent);
        }
        $this->renderer->addCacheableDependency($build, $term);

        return [
            'action' => $this->buildActionLinks($config->get('vocabulary'), $term),
            'forum' => $build,
            '#cache' => [
                'tags' => Cache::mergeTags($this->nodeEntityTypeDefinition->getListCacheTags(), $this->commentEntityTypeDefinition->getListCacheTags())
            ]
        ];
    }

    /**
     * Returns forum page for a given forum.
     *
     * @param \Drupal\taxonomy\TermInterface $taxonomy_term
     *            The forum to render the page for.
     *
     * @return array A render array.
     */
    public function forumPage(TermInterface $taxonomy_term)
    {
        // Get forum details.
        $taxonomy_term->forums = $this->forumManager->getChildren($this->config('forum.settings')
            ->get('vocabulary'), $taxonomy_term->id());
        $taxonomy_term->parents = $this->forumManager->getParents($taxonomy_term->id());

        if (empty($taxonomy_term->forum_container->value)) {
            $build = $this->forumManager->getTopics($taxonomy_term->id(), $this->currentUser());
            $topics = $build['topics'];
            $header = $build['header'];
        } else {
            $topics = [];
            $header = [];
        }
        $foo = &drupal_static(__TOPIC__, $build['topics']);
        return $this->build($taxonomy_term->forums, $taxonomy_term, $topics, $taxonomy_term->parents, $header);
    }

    /**
     * Forum subjets without replies.
     *
     * Return json array of comments.
     */
    public function subjectNoComments()
    {
        // load all members in forum
        $content = [];
        $numPerPage = $this->defaultLimitForum ?? 25;
        $currentUser = \Drupal::currentUser();
        $db = \Drupal::database();
        // get total count
        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('fi.comment_count', 0);
        $total = $query->condition('nfd.status', 1)
            ->countQuery()
            ->execute()
            ->fetchField();
        // pager
        $page = pager_default_initialize($total, $numPerPage);
        $offset = $numPerPage * $page;

        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('fi.comment_count', 0);
        $query->condition('nfd.status', 1);
        $query->range($offset, $numPerPage);
        $query->orderby('fi.last_comment_timestamp', 'DESC');
        $result = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($result);
        if (! empty($nids)) {
            $forum = self::getForumTopics($nids, $currentUser);
            $content = self::processTopics($forum);
        }
        // add hidden route
        $url = Url::fromRoute('mtc_core.lc_forum_controller_subject_no_comments')->toString();
        $searchRoute = '<a id="search_url" class="visually-hidden" data-href="' . $url . '"></a>';
        $content = '<div class="forum-subject">' . render($content) . $searchRoute . '</div>';

      $page = \Drupal::request()->query->get('page', '');
      if ($page == '0' || !empty($page)) {
        $response = new Response();
        $response->setContent(json_encode(array(
          'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }
      else {
        return $this->AjaxCommandForumTabs($content, $url);
      }

    }

    /**
     * Forum subjets that are active (more than 5 comments).
     *
     * Return json array of comments.
     */
    public function subjectActive()
    {
        $content = [];
        $activeNum = $this->activeCommentNum ?? 5;
        $numPerPage = $this->defaultLimitForum ?? 25;

        $currentUser = \Drupal::currentUser();

        $db = \Drupal::database();

        // get total count
        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('fi.comment_count', $activeNum, '>');
        $total = $query->condition('nfd.status', 1)
            ->countQuery()
            ->execute()
            ->fetchField();
        // pager
        $page = pager_default_initialize($total, $numPerPage);
        $offset = $numPerPage * $page;

        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('fi.comment_count', $activeNum, '>');
        $query->condition('nfd.status', 1);
        $query->range($offset, $numPerPage);
        $query->orderby('fi.last_comment_timestamp', 'DESC');
        $result = $query->execute()->fetchAllAssoc('nid');

        $nids = array_keys($result);
        if (! empty($nids)) {
            $forum = self::getForumTopics($nids, $currentUser);
            $content = self::processTopics($forum);
        }
        // add hidden route
        $url = Url::fromRoute('mtc_core.lc_forum_controller_subject_active')->toString();
        $searchRoute = '<a id="search_url" class="visually-hidden" data-href="' . $url . '"></a>';
        $content = '<div class="forum-subject">' . render($content) . $searchRoute . '</div>';

      $page = \Drupal::request()->query->get('page', '');
      if ($page == '0' || !empty($page)) {
        $response = new Response();
        $response->setContent(json_encode(array(
          'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }
      else {
        return $this->AjaxCommandForumTabs($content, $url);
      }

    }

    /**
     * Forum subjets that are new based on last comment
     *
     * Return json array of comments.
     */
    public function subjectNew()
    {
        // load all members in forum
        $content = [];
        $numPerPage = $this->defaultLimitForum ?? 25;
        $currentUser = \Drupal::currentUser();

        $db = \Drupal::database();

        // get total count
        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $total = $query->condition('nfd.status', 1)
            ->countQuery()
            ->execute()
            ->fetchField();
        // pager
        $page = pager_default_initialize($total, $numPerPage);
        $offset = $numPerPage * $page;

        $query = $db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('nfd.status', 1);
        $query->range($offset, $numPerPage);
        $query->orderby('fi.last_comment_timestamp', 'DESC');
        $result = $query->execute()->fetchAllAssoc('nid');
        $nids = array_keys($result);

        if (! empty($nids)) {
            $forum = self::getForumTopics($nids, $currentUser);
            $content = self::processTopics($forum);
        }
        // add hidden route
        $url = Url::fromRoute('mtc_core.lc_forum_controller_subject_new')->toString();
        $searchRoute = '<a id="search_url" class="visually-hidden" data-href="' . $url . '"></a>';
        $content = '<div class="forum-subject">' . render($content) . $searchRoute . '</div>';

      $page = \Drupal::request()->query->get('page', '');
      if ($page == '0' || !empty($page)) {
        $response = new Response();
        $response->setContent(json_encode(array(
          'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }
      else {
        return $this->AjaxCommandForumTabs($content, $url);
      }

    }

    /**
     * Forum subjets that are new based on last comment
     *
     * Return json array of comments.
     */
    public function forumMembers($searchText = null)
    {
        // load all members in forum
        $content = [];

        $roles = [
          'utilisateur_abonne',
          'filtered_ancien_abonne',
          'filtered_inscrit',
        ];

        $numPerPage = $this->$defaultPagination ?? 16;
        $db = \Drupal::database();
        // find count result
        $query = $db->select('users_field_data', 'ufd');
        $query->addField('ufd', 'name', 'user_name');
        $query->leftJoin('user__user_picture', 'uup', 'uup.entity_id = ufd.uid');
        $query->leftJoin('user__roles', 'ur', 'ur.entity_id = uup.entity_id');

        $query->leftJoin('file_managed', 'fm', 'fm.fid = uup.entity_id');

        // @todo remove constants
        $query->condition('ur.roles_target_id', $roles, 'IN');
        $query->condition('ur.roles_target_id', array(
            'administrator',
            'utilisateur_admin',
            'developpeur'
        ), 'NOT IN');
        if (! empty($searchText)) {
            $query->condition('ufd.name', '%' . $searchText . '%', 'LIKE');
        }
        $total = $query->condition('ufd.status', 1)
            ->countQuery()
            ->execute()
            ->fetchField();

        $page = pager_default_initialize($total, $numPerPage);
        $offset = $numPerPage * $page;

        $query = $db->select('users_field_data', 'ufd');
        $query->addField('ufd', 'name', 'user_name');
        $query->addField('ufd', 'created', 'created');
        $query->addField('ufd', 'access', 'access');
        $query->addField('ufd', 'uid', 'uid');
        $query->addField('upu', 'ville', 'ville');
        $query->addField('fm', 'uri', 'file_uri');
        $query->leftJoin('user__user_picture', 'uup', 'uup.entity_id = ufd.uid');
        $query->leftJoin('file_managed', 'fm', 'fm.fid = uup.user_picture_target_id');
        $query->leftJoin('user__roles', 'ur', 'ur.entity_id = uup.entity_id');
        $query->leftJoin('lc_user_profile_entity', 'upu', 'upu.user_id = ufd.uid');
        $query->addExpression('IF (ufd.access > :time, 1, 0)', 'online', [':time' => REQUEST_TIME - 900]);
        $query->addExpression('(SELECT GROUP_CONCAT(user__roles.roles_target_id) FROM  user__roles WHERE user__roles.entity_id = ufd.uid)', 'roles');
        $query->condition('ur.roles_target_id', $roles, 'IN');
        $query->distinct();
        $query->condition('ur.roles_target_id', array(
            'administrator',
            'utilisateur_admin',
            'developpeur'
        ), 'NOT IN');
        $query->condition('ufd.status', 1);
        if (! empty($searchText)) {
            $group = $query->orConditionGroup();
            $group->condition('ufd.name', '%' . $searchText . '%', 'LIKE');
            $group->condition('upu.ville', '%' . $searchText . '%', 'LIKE');
            $query->condition($group);
        }
        $query->range($offset, $numPerPage);
        $query->orderby('online', 'DESC');
        $query->orderby('ufd.name', 'ASC');

        $result = $query->execute()->fetchAll();
        // /format result
        foreach ($result as $res) {
            // check if person is actively connected, with delay of 900 seconds

              $roleList = explode(',', $res->roles);
              $flag = \Drupal::service('flag.link_builder');
              $flag_link = $flag->build('user', $res->uid, 'following');
              $rendered_link = drupal_render($flag_link);
              $uri = $res->file_uri;
              $content[] = [
                'online' => $res->online,
                'user_name' => $res->user_name,
                'file_uri' => $uri,
                'created' => date("Y-m-d", $res->created),
                'roles' => _mtc_core_roles_validation($roleList),
                'uid' => $res->uid,
                'city' => $res->ville,
                'flag' => $rendered_link
              ];
        }

        $contenToRender = [];
        $contenToRender[] = [
            '#type' => 'pager'
        ];
        $contenToRender[] = [
            '#theme' => 'mtc_core_forum_members_ajax',
            '#content' => $content,
            '#attributes' => [
                'class' => [
                    'forum-members'
                ]
            ],
            '#route_name' => 'mtc_core.lc_forum_controller_forum_members'
        ];
        $contenToRender[] = [
            '#type' => 'pager'
        ];

        $content = '<div class="all-members">' . render($contenToRender) . '</div>';
        if(empty($result)) {
            $content .= '<div class="no-result">' . t('0 résultat pour "@searchText"', ['@searchText' => $searchText]) . '</div>';
        }

        $response = new Response();
        $response->setContent(json_encode(array(
            'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /*
     * Function that marks all topics as all read
     *
     */
    public function topicsMarkAllRead()
    {
        $currentUser = \Drupal::currentUser();
        $uid = $currentUser->id();
        $tid = null;
        $vid = 'forums';
        // case it anonymous
        if (empty($uid)) {
            $response = new Response('', 401);
            return $response;
        }

        // get forum page
        $term = \Drupal::request()->attributes->get('taxonomy_term');
        if ($term && $term->getVocabularyId() == $vid) {
            $tid = $term->get('tid')->value;
        }

        $query = db_select("node_field_data", "nfd");
        $query->join('forum', 'f', 'nfd.vid = f.vid');
        $query->join('taxonomy_term_data', 't', 'f.tid = t.tid');

        if (isset($tid)) {
            $query->condition('f.tid', $tid);
        }
        $query->condition('t.vid', $vid);
        $query->addField('nfd', 'nid');
        // Select query objects are one-shot, so clone for INSERT below.
        $query_history_insert = clone ($query);
        // Delete values based upon sub-query.
        $query = db_delete('history')->condition('uid', $uid)
            ->condition('nid', $query, 'IN')
            ->execute();

        // Now insert the nids into the history table.
        $query_history_insert->addExpression(':uid', 'uid', array(
            ':uid' => $uid
        ));
        $query_history_insert->addExpression(':time', 'timestamp', array(
            ':time' => REQUEST_TIME
        ));

        db_insert('history')->fields(array(
            'nid',
            'uid',
            'timestamp'
        ))
            ->from($query_history_insert)
            ->execute();

        $response = new Response();
        $response->setContent(json_encode(array(
            'content' => true
        )));
        return $response;
    }
  /**
   * Forum subjets that are active (more than 5 comments).
   *
   * Return json array of comments.
   */
  public function loadUsersFollowers()
  {
    $content = [];
    $numPerPage = $this->defaultLimitForum ?? 25;
    $currentUser = \Drupal::currentUser();
    $db = \Drupal::database();

    $query = $db->select('flagging', 'fl');
    $query->fields('fl',['entity_id']);
    $query->condition('fl.flag_id', 'following_forum_post');
    $query->condition('fl.uid', $currentUser->getAccount()->id());
    $total = $query->execute()->fetchCol();

    $url = Url::fromRoute('mtc_core.lc_forum_controller_users_followers')->toString();
    if (!empty($total)) {
      $count = $query->countQuery()->execute()->fetchField();

      //pager
      $page = pager_default_initialize($count, $numPerPage);
      $offset = $numPerPage * $page;

      $query = $db->select('forum_index', 'fi');
      $query->fields('fi', [
        'nid'
      ]);
      $query->condition('fi.nid', $total, 'IN');
      $query->range($offset, $numPerPage);
      $query->orderBy('fi.created', 'DESC');
      $result = $query->execute()->fetchAllAssoc('nid');

      $nids = array_keys($result);
      if (! empty($nids)) {
        $forum = self::getForumTopics($nids, $currentUser);
        $content = self::processTopics($forum);
      }
      // add hidden route
      $searchRoute = '<a id="search_url" class="visually-hidden" data-href="' . $url . '"></a>';
      $content = '<div class="forum-subject">' . render($content) . $searchRoute . '</div>';

    }
    else {
      $content = '<p class="empty-text">Vous retrouverez ici tous les fils de discussions pour lesquels vous avez actionné l\'option "Suivre".

En suivant un fil de discussions, vous recevrez également par e-mail, une notification lorsqu\'un nouveau commentaire sera posté.</p>';
    }


    $page = \Drupal::request()->query->get('page', '');
    if ($page == '0' || !empty($page)) {
      $response = new Response();
      $response->setContent(json_encode(array(
        'content' => $content
      )));
      $response->headers->set('Content-Type', 'application/json');
      return $response;
    }
    else {
      return $this->AjaxCommandForumTabs($content, $url);
    }

  }

  public function AjaxCommandForumTabs($content, $url) {
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('article.forum-container', $content));
    $response->addCommand(new InvokeCommand('article .action-links', 'hide', [0]));
    $response->addCommand(new InvokeCommand('#forum-nav li span', 'removeClass', ['active']));
    $response->addCommand(new InvokeCommand("span[data-href='$url']", 'addClass', ['active']));
    return $response;
  }
}
