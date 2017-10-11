<?php
namespace Drupal\mtc_core\Traits;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

trait ForumTrait {

    /* Maximum number of forum topics in sql query */
    public $defaultLimitForum = 25;

    /* Number of active comments */
    public $activeCommentNum = 3;

    /* Default pagination num for forum members */
    /*
     * Function that obtains the list of forum topics from nids
     * @nids array of node nids
     * @account current user
     * @return array
     */
    public function getForumTopics($nids, AccountInterface $account)
    {
        $header = [
            [
                'data' => $this->t('Topic')
            ],
            [
                'data' => $this->t('Replies')
            ],
            [
                'data' => $this->t('Last reply')
            ]
        ];
        $entityManager = \Drupal::entityTypeManager();
        $forumManager = $date = \Drupal::service('forum_manager');
        $connection = Database::getConnection();
        $nodes = $entityManager->getStorage('node')->loadMultiple($nids);
        $query = $connection->select('node_field_data', 'n');
        $query->fields('n', array(
            'nid'
        ));

        $query->join('comment_entity_statistics', 'ces', "n.nid = ces.entity_id AND ces.field_name = 'comment_forum' AND ces.entity_type = 'node'");
        $query->fields('ces', array(
            'cid',
            'last_comment_uid',
            'last_comment_timestamp',
            'comment_count'
        ));

        $query->join('forum_index', 'f', 'f.nid = n.nid');
        $query->addField('f', 'tid', 'forum_tid');

        $query->join('users_field_data', 'u', 'n.uid = u.uid AND u.default_langcode = 1');
        $query->addField('u', 'name');

        $query->join('users_field_data', 'u2', 'ces.last_comment_uid = u2.uid AND u.default_langcode = 1');

        $query->addExpression('CASE ces.last_comment_uid WHEN 0 THEN ces.last_comment_name ELSE u2.name END', 'last_comment_name');

        $query->orderBy('f.sticky', 'DESC')
            ->condition('n.nid', $nids, 'IN')
            ->
        // @todo This should be actually filtering on the desired node language
        // and just fall back to the default language.
        condition('n.default_langcode', 1);

        $result = array();
        foreach ($query->execute() as $row) {
            $topic = $nodes[$row->nid];
            $topic->comment_mode = $topic->comment_forum->status;

            foreach ($row as $key => $value) {
                $topic->{$key} = $value;
            }
            $result[] = $topic;
        }
        $topics = array();
        $first_new_found = FALSE;
        foreach ($result as $topic) {
            if ($account->isAuthenticated()) {
                // A forum is new if the topic is new, or if there are new comments since
                // the user's last visit.
                $history = $this->lastVisit($topic->id(), $account);
                $topic->new_replies = \Drupal::service('comment.manager')->getCountNewComments($topic, 'comment_forum', $history);
                $topic->new = $topic->new_replies || ($topic->last_comment_timestamp > $history);
            } else {
                // Do not track "new replies" status for topics if the user is anonymous.
                $topic->new_replies = 0;
                $topic->new = 0;
            }

            // Make sure only one topic is indicated as the first new topic.
            $topic->first_new = FALSE;
            if ($topic->new != 0 && ! $first_new_found) {
                $topic->first_new = TRUE;
                $first_new_found = TRUE;
            }

            if ($topic->comment_count > 0) {
                $last_reply = new \stdClass();
                $last_reply->created = $topic->last_comment_timestamp;
                $last_reply->name = $topic->last_comment_name;
                $last_reply->uid = $topic->last_comment_uid;
                $topic->last_reply = $last_reply;
            }
            $topics[$topic->id()] = $topic;
        }
        return array(
            'topics' => $topics,
            'header' => $header
        );
    }

    /**
     * Funtion that processes topic variables
     *
     * @param unknown $variables
     */
    public function processTopics($forum)
    {
        $content = [];
        $table = array(
            '#theme' => 'table__forum_topic_list',
            '#responsive' => FALSE,
            '#attributes' => array(
                'id' => 'forum-topic-ajax'
            ),
            '#header' => $forum['header'],
            '#rows' => array()
        );
        /** @var \Drupal\node\NodeInterface $topic */
        foreach ($forum['topics'] as $id => $topic) {
            $content['topics'][$id] = new \stdclass();
            $content['topics'][$id]->icon = array(
                '#theme' => 'forum_icon',
                '#new_posts' => $topic->new ?? false,
                '#num_posts' => $topic->comment_count ?? 0,
                '#comment_mode' => $topic->comment_mode ?? '',
                '#sticky' => $topic->isSticky(),
                '#first_new' => $topic->first_new ?? false
            );

            $content['topics'][$id]->moved = FALSE;
            $content['topics'][$id]->title_link = \Drupal::l($topic->getTitle(), $topic->urlInfo());
            $content['topics'][$id]->message = '';
            $forum_submitted = array(
                '#theme' => 'forum_submitted',
                '#topic' => (object) array(
                    'uid' => $topic->getOwnerId(),
                    'name' => $topic->getOwner()->getDisplayName(),
                    'created' => $topic->getCreatedTime()
                )
            );

            $content['topics'][$id]->submitted = drupal_render($forum_submitted);
            $forum_submitted = array(
                '#theme' => 'forum_submitted',
                '#topic' => isset($topic->last_reply) ? $topic->last_reply : NULL
            );

            $content['topics'][$id]->last_reply = drupal_render($forum_submitted);

            $content['topics'][$id]->new_text = '';
            $content['topics'][$id]->new_url = '';

            if ($topic->new_replies) {
                $page_number = \Drupal::entityManager()->getStorage('comment')->getNewCommentPageNumber($topic->comment_count, $topic->new_replies, $topic, 'comment_forum');
                $query = $page_number ? array(
                    'page' => $page_number
                ) : NULL;
                if (isset($variables['topics'][$id])) {
                    $content['topics'][$id]->new_text = \Drupal::translation()->formatPlural($topic->new_replies, '1 new post<span class="visually-hidden"> in topic %title</span>', '@count new posts<span class="visually-hidden"> in topic %title</span>', array(
                        '%title' => $variables['topics'][$id]->label()
                    ));
                    $content['topics'][$id]->new_url = \Drupal::url('entity.node.canonical', [
                        'node' => $topic->id()
                    ], [
                        'query' => $query,
                        'fragment' => 'new'
                    ]);
                }
                // $content['topics'][$id]->new_text = \Drupal::translation()->formatPlural($topic->new_replies, '1 new post<span class="visually-hidden"> in topic %title</span>', '@count new posts<span class="visually-hidden"> in topic %title</span>', array('%title' => ''));
                // $content['topics'][$id]->new_url = \Drupal::url('entity.node.canonical', ['node' => $topic->id()], ['query' => $query, 'fragment' => 'new']);
            }

            // Build table rows from topics.
            $row = [];
            $comment_statistic = \Drupal::getContainer()->get('comment.statistics')->read([$topic->id()=>$topic],'node');
             $icon = ' ';
            $limitHot['hot_threshold'] = \Drupal::config('forum.settings')->get('topics.hot_threshold');

            if (\Drupal::getContainer()->get('comment.manager')->getCountNewComments($topic)) {
              $icon = 'new';
            }

            if ($comment_statistic[0]->comment_count > $limitHot['hot_threshold']) {
              $icon = 'hot';
            }

            $row[] = [
                'data' => [
                    [
                        '#markup' => '<div class="forum-icon fa ' . $icon . '"></div><div class="forum__title"><div>' . $content['topics'][$id]->title_link . '</div><div>' . $content['topics'][$id]->submitted . '</div></div>'
                    ]
                ],
                'class' => [
                    'forum__topic',
                ]
            ];

            $new_replies = '';
            if ($topic->new_replies) {
                $new_replies = '<br /><a href="' . $content['topics'][$id]->new_url . '">' . $content['topics'][$id]->new_text . '</a>';
            }

            $row[] = array(
                'data' => [
                    [
                        '#prefix' => $topic->comment_count,
                        '#markup' => $topic->new_replies
                    ]
                ],
                'class' => array(
                    'forum__replies'
                )
            );
            $row[] = array(
                'data' => [
                    '#markup' => $this->getLastCommentPost($topic) . $content['topics'][$id]->last_reply,
                ],
                'class' => array(
                    'forum__last-reply'
                ),
                'data-node-id' => $id,
            );
            $table['#rows'][] = $row;
        }
        $table['#topics'] = $content['topics'];
        $content['topics'] = $table;
        $content['topics_pager'] = array(
            '#type' => 'pager'
        );
        return $content;
    }

    /**
     * Get the last comment the user.
     *
     * @return \Drupal\Core\GeneratedLink|string
     */
    public function getLastCommentPost($topic) {
        $route = \Drupal::routeMatch()->getRouteName();
        if($route == 'mtc_core.lc_forum_controller_subject_no_comments') {
            return;
        }
        elseif (!empty($topic->last_comment_uid)) {
            $query = \Drupal::entityQuery('comment');
            $result = $query->condition('entity_type', 'node')
              ->condition('created', $topic->last_comment_timestamp)
              ->condition('uid', $topic->last_comment_uid)
              ->sort('cid', 'DESC')
              ->execute();
            /** @var \Drupal\comment\Entity\Comment $comment */
            $comment = \Drupal\comment\Entity\Comment::load(reset($result));
            if ($comment) {
                $safe_string = trim(html_entity_decode(strip_tags($comment->comment_body->value), ENT_QUOTES));
                $safe_string = \Drupal\Component\Utility\Unicode::truncate($safe_string, 20, FALSE, TRUE);
                /** @var \Drupal\Core\GeneratedLink $link */
                $link = $comment->link($safe_string, 'canonical', ['fragment' => $comment->id()]);
                return "<span>$link</span>";
            }
            else {
                $query = \Drupal::service('entity.query')
                  ->get('node')
                  ->condition('created', $topic->last_comment_timestamp)
                  ->condition('uid', $topic->last_comment_uid)
                  ->execute();
                /** @var \Drupal\node\Entity\Node $node */
                $node = Node::load(reset($query));
                if ($node) {
                    $body = trim(html_entity_decode(strip_tags($node->get('body')->value), ENT_QUOTES));
                    $body = \Drupal\Component\Utility\Unicode::truncate($body, 20, FALSE, TRUE);
                    $link = $node->link($body, 'canonical');
                    return "<span>$link</span>";
                }
            }
        }
    }
    
    /**
     * Gets the last time the user viewed a node.
     *
     * @param int $nid
     *            The node ID.
     * @param \Drupal\Core\Session\AccountInterface $account
     *            Account to fetch last time for.
     *
     * @return int The timestamp when the user last viewed this node, if the user has
     *         previously viewed the node; otherwise HISTORY_READ_LIMIT.
     */
    public function lastVisit($nid, AccountInterface $account)
    {
        if (empty($this->history[$nid])) {
            $result = Database::getConnection()->select('history', 'h')
                ->fields('h', array(
                'nid',
                'timestamp'
            ))
                ->condition('uid', $account->id())
                ->execute();
            foreach ($result as $t) {
                $this->history[$t->nid] = $t->timestamp > HISTORY_READ_LIMIT ? $t->timestamp : HISTORY_READ_LIMIT;
            }
        }
        return isset($this->history[$nid]) ? $this->history[$nid] : HISTORY_READ_LIMIT;
    }
}
