<?php
namespace Drupal\mtc_core\TwigExtension;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Html;
use Drupal\mtc_core\Utils\TaxonomyUtility;
use Drupal\Core\Template\TwigExtension;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * A Twig extension that adds a custom function and a custom filter.
 */
class LinecoachingTwigExtension extends TwigExtension
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
            new \Twig_SimpleFunction('display_block', [
                $this,
                'displayBlock'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_humour', [
                $this,
                'displayHumour'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_menu', [
                $this,
                'displayMenu'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_bilan_gratuit', [
                $this,
                'displayBilanGratuit'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_taxonomy', [
                $this,
                'displayTaxonomy'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('obtain_term_information', [
                $this,
                'obtainTermInformation'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('default_fivestar_widget', [
                $this,
                'defaultFivestarWidget'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('drupal_view_with_title', [
                $this,
                'drupalViewWithTitle'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('get_file_uri', [
                $this,
                'getFileUri'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('get_user_picto', [
                $this,
                'getUserPicto'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_og_chatrooms', [
                $this,
                'displayOgChatrooms'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('display_og_chat_archive', [
                $this,
                'displayOgChatArchive'
            ], [
                'is_safe' => [
                    'html'
                ]
            ]),
            new \Twig_SimpleFunction('get_node_content_type', [
                $this,
                'getPageContentType'
            ], [
                'is_safe' => [
                    'html'
                ]
            ])
        ];
    }

    /*
     * Function that gets the current content type
     */
    public function getPageContentType()
    {
        $typeName = null;
        $node = \Drupal::routeMatch()->getParameter('node');
        if ($node) {
            $typeName = $node->bundle();
        }
        return $typeName;
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
            new \Twig_SimpleFilter('truncate', [
                'Drupal\Component\Utility\Unicode',
                'truncate'
            ]),
            new \Twig_SimpleFilter('display_tag_content', [
                $this,
                'displayTagContent'
            ]),
            new \Twig_SimpleFilter('comment_count', [
                $this,
                'commentCount'
            ]),
            new \Twig_SimpleFilter('get_video_thumbnail', [
                $this,
                'getVideoThumbnail'
            ]),
            new \Twig_SimpleFilter('get_video_url', [
                $this,
                'getVideoUrl'
            ]),
            new \Twig_SimpleFilter('strip_tags_allow_entity', [
                $this,
                'stripTagsAllowEntity'
            ]),
            new \Twig_SimpleFilter('responsive_image', [
                $this,
                'getResponsiveImage'
            ]),
            new \Twig_SimpleFilter('field_collection_items', [
                $this,
                'getFieldCollectionItem'
            ]),
            new \Twig_SimpleFilter('get_target_nodes', [
                $this,
                'getTargetNodes'
            ]),
            new \Twig_SimpleFilter('convert_time_to_minutes', [
                $this,
                'convertTimeToMinutes'
            ]),
            new \Twig_SimpleFilter('relative_to_absolute', [
                $this,
                'relativeToAbsolute'
            ]),
            new \Twig_SimpleFilter('flag_counter', [
                $this,
                'flagCounter'
            ]),
            new \Twig_SimpleFilter('get_image_from_fid', [
                $this,
                'getImageFromFid'
            ])
        ];
    }

    /*
     *
     */
    public function displayOgChatArchive($nid)
    {
        if (empty($nid))
            return null;
        $nid = (int) $nid;
        $user = \Drupal::currentUser();

        $db = \Drupal::database();
        $query = $db->select('node__field_chatroom', 'nfc');
        $query->addField('nfc', 'field_chatroom_target_id', 'chatRoomId');
        $query->join('node_field_data', 'n', 'n.nid = nfc.entity_id ');
        $query->join('node__field_status_chat', 'nfsc', 'nfsc.entity_id = n.nid ');

        $query->orderBy('n.created', 'DESC')
            ->condition('n.status', 1, '=')
            ->condition('nfc.entity_id', $nid, '=');
        $result = $query->execute()->fetchObject();
        if (! $result)
            return null;
        $chatRoom = \Drupal\chatroom\Entity\Chatroom::load($result->chatRoomId);
        $messages = $chatRoom->loadMessages();
        $rows = [];
        // foramt messages
        foreach ($messages as $index => $message) {
            $created = $message->get('created')->value;
            $created = \Drupal::service('date.formatter')->format($created, 'short', 'd/m/y - H:m');
            $uid = $message->get('uid')->getString();
            // load user
            $user = \Drupal\user\Entity\User::load($uid);
            // create user link
            // create title link
            $urlObject = Url::fromRoute('entity.user.canonical', [
                'user' => $uid
            ], [
                'absolute' => TRUE
            ]);
            $title = Link::fromTextAndUrl(ucfirst($user->getAccountName()), $urlObject);
            // default image
            $imageUrl = file_url_transform_relative(file_create_url('public://avatar_selection/anonyme.jpg'));
            if ($user->get('user_picture')->entity) {
                $imageUrl = $user->get('user_picture')->entity->url();
            }
            $image = 'background-image:url(' . $imageUrl . ')';
            $rowClass = $user->hasRole('votre_coach') ? 'coach chatroom-message' : 'chatroom-message';
            $rows[] = [
                'data' => [
                    $created,
                    'image' => [
                        'style' => $image,
                        'class' => 'chatroom-avatar'
                    ],
                    $title,
                    $message->get('text')->value
                ],
                'class' => [
                    $rowClass
                ]
            ];
        }
        return [
            '#theme' => 'table',
            '#header' => [''],
            '#rows' => $rows,
            '#attributes' => [
                'class' => [
                    'chatroom-archived'
                ]
            ]
        ];
    }

    /*
     * Display og chat rooms
     */
    public function displayOgChatrooms($ogGroupId)
    {
        // @todo, add pagination
        if (empty($ogGroupId))
            return null;
        $ogGroupId = (int) $ogGroupId;
        $user = \Drupal::currentUser();

        $db = \Drupal::database();
        // get chat list of same group
        $query = $db->select('node_field_data', 'n');
        $query->fields('n', [
            'nid',
            'title',
            'uid',
            'changed',
            'type',
            'created'
        ]);
        $query->join('node__og_audience', 'noga', 'n.nid = noga.entity_id ');
        $query->join('node__field_chatroom', 'nfc', 'nfc.entity_id = n.nid ');
        $query->leftJoin('node__field_status_chat', 'nfsc', 'nfsc.entity_id = n.nid ');
        $query->leftJoin('node__field_date_chat', 'nfdc', 'nfdc.entity_id = n.nid ');

        $query->addField('nfc', 'field_chatroom_target_id', 'chatRoomId');
        $query->addField('nfsc', 'field_status_chat_value', 'archiveStatus');
        $query->addField('nfdc', 'field_date_chat_value', 'date_chat_value');

        $query->orderBy('nfdc.field_date_chat_value', 'desc')
            ->condition('n.status', 1, '=')
            ->condition('noga.og_audience_target_id', $ogGroupId, '=');
        $result = $query->execute();
        $header['archived'] = [
            'Nom de la discussion',
            'Nombre de messages',
            'Archivé le:'
        ];
        $header['active'] = [
            'Nom de la discussion',
            'Nombre de messages',
            'Dernier message'
        ];
        $rows = [];

        // construct table list
        foreach ($result as $chat) {
            $lastMessage = '';
            $chatRoom = \Drupal\chatroom\Entity\Chatroom::load($chat->chatRoomId);
            $chatMessage = $chatRoom->loadLatestMessages(1);
            $messageCnt = $chatRoom->getMessageCount();
            $chatMessage = is_array($chatMessage) ? array_shift($chatMessage) : null;
            // create title link
            $urlObject = Url::fromRoute('entity.node.canonical', [
                'node' => $chat->nid
            ], [
                'absolute' => TRUE
            ]);
            $title = Link::fromTextAndUrl($chat->title, $urlObject);
            if ($chatMessage) {
                $lastMessage['text'] = $chatMessage->get('text')->value;
                $lastMessage['created'] = $chatMessage->get('created')->value;
                $lastMessage = $lastMessage['text'] . '  ' . \Drupal::service('date.formatter')->format($lastMessage['created'], 'short', 'd/m/y - H:m');
            }
            if ($chat->archiveStatus == 1) {
                $rows['archived'][] = [
                    $title,
                    $messageCnt,
                    'Archivé le ' . \Drupal::service('date.formatter')->format($chat->created, 'short', 'd/m/y - H:m')
                ];
            } else {
                $rows['active'][] = [
                    $title,
                    $messageCnt,
                    $lastMessage
                ];
            }
        }
        // render tables
        $buildActive = [
            '#theme' => 'table',
            '#header' => $header['active'],
            '#rows' => $rows['active'] ?? '',
            '#prefix' => '<span class="og-title">Discussions ouvertes dans ce salon</span>'
        ];
        $buildArchived = [
            '#theme' => 'table',
            '#header' => $header['archived'],
            '#rows' => $rows['archived'] ?? '',
            '#prefix' => '<span class="og-title">Discussions archivées dans ce salon </span>'
        ];
        $output = isset($rows['active']) ? drupal_render($buildActive) : '';
        $output .= isset($rows['archived']) ? drupal_render($buildArchived) : '';

        return $output;
    }

    /**
     * Function that converts relative links to absolute
     *
     * @param
     *            array link
     * @return string
     */
    public function relativeToAbsolute($link)
    {
        global $base_secure_url, $base_insecure_url, $base_url, $base_path, $base_root;
        $domain = \Drupal::request()->isSecure() ? $base_secure_url : $base_insecure_url;
        $currentRoute = \Drupal::routeMatch()->getRouteName();
        if ($currentRoute != 'mtc_core.lc_cobranding_controller_themeRegions') {
            return $link;
        }
        if (! is_string($link)) {
            return $link;
        }
        if ($link[0] == '/') {
            return $domain . $link;
        }
        // case of internal,convert to absolute
        if (substr($link, 0, 10) == "internal:/") {
            return $domain . substr($link, 9);
        }
        return $link;
    }

    /**
     * Function array of items nodes from target_id (entity reference field).
     *
     * @return Array content of Node
     */
    public function getTargetNodes($items)
    {
        if (empty($items))
            return '';
        $result = [];
        foreach ($items as $item) {
            $targetId = $item->get('target_id')->getValue() ?? NULL;
            if (empty($targetId))
                continue;
            $result[] = \Drupal\node\Entity\Node::load($targetId);
        }
        return $result;
    }

    /**
     * Function that converts a field collection into array of items.
     *
     * @return Array content
     */
    public function getFieldCollectionItem($items)
    {
        if (empty($items))
            return '';
        $result = [];
        foreach ($items as $item) {

            $fields = \Drupal\field_collection\Entity\FieldCollectionItem::load($item->value);
            if (empty($fields))
                return NULL;
            $chapeau = $fields->hasField('field_chapeau') ? $fields->get('field_chapeau') : NULL;
            $image = $fields->hasField('field_image') ? $fields->get('field_image') : NULL;
            $title = $fields->hasField('field_title') ? $fields->get('field_title') : NULL;
            $videoLinkText = $fields->hasField('field_video_link_text') ? $fields->get('field_video_link_text') : NULL;

            $chapeau = ! empty($chapeau) ? $chapeau->getValue() : NULL;
            $title = ! empty($title) ? $title->getValue() : NULL;
            $image = ! empty($image) ? $image->entity : NULL;

            $chapeau = ! empty($chapeau) ? $chapeau[0]['value'] : NULL;
            $title = ! empty($title) ? $title[0]['value'] : NULl;
            $videoLinkText = ! empty($videoLinkText) ? $videoLinkText->getValue() : NULL;
            $videoLinkText = ! empty($videoLinkText) ? $videoLinkText[0]['value'] : NULL;

            $fileUrl = NULL;
            // get image
            if (isset($image) && isset($image->uri)) {
                $fileUrl = $image->uri->value;
            }
            $result[$item->value]['fileuri'] = $fileUrl;
            $result[$item->value]['text'] = $chapeau;
            $result[$item->value]['title'] = $title;
            $result[$item->value]['video_link_text'] = $videoLinkText;
        }
        return $result;
    }

    /**
     * Function that converts a taxonomy tag to html.
     *
     * @param $tag object
     *            (drupal field)
     * @param $link boolean
     *            (return if true)
     * @param $parentLevel boolean
     *            (return if true)
     * @return string
     */
    public function displayTagContent($tag, $link = false, $parentLevel = null)
    {
        $targetId = NULL;
        if (! is_object($tag) || ! method_exists($tag, 'getValue'))
            return '';
        $val = $tag->getValue();
        if (! isset($val[0]['target_id']))
            return '';
        $targetId = $val[0]['target_id'];
        // get parent Term level if it exists
        $targetId = TaxonomyUtility::getParentTerm($targetId, $parentLevel);
        $term = \Drupal\taxonomy\Entity\Term::load($targetId);
        if (! $term)
            return '';
        $termName = ucfirst($term->getName());
        // clean class names
        $className = Html::cleanCssIdentifier($term->getName());
        $termVid = Html::cleanCssIdentifier($term->getVocabularyId());
        // add link if defined
        if ($link) {
            $term_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/' . $targetId);
            return '<div class="tag-content ' . $termVid . '  ' . $className . ' ">
                    <a class="tag-content-link"  title="' . $termName . '" href="' . $term_alias . '">' . $termName . '</a></div>';
        }
        return '<div class="tag-content ' . $termVid . '  ' . $className . ' ">' . $termName . '</div>';
    }

    /**
     * Function that obtains comment count from nid.
     *
     * @param
     *            $nid
     * @return int comment count
     */
    public function commentCount($nid)
    {
        if (empty($nid))
            return 0;
        $nid = (int) $nid;
        $num_comment = db_query('SELECT comment_count FROM {comment_entity_statistics} WHERE entity_id = :nid', [
            ':nid' => $nid
        ])->fetchAssoc();
        return $num_comment['comment_count'] ?? 0;
    }

    /**
     * Function that obtains comment count from nid.
     *
     * @param
     *            $nid
     * @return int comment count
     */
    public function flagCounter($id)
    {
        if (empty($id))
            return 0;
        $nid = (int) $id;
        $queryCnt = db_query('SELECT count FROM {flag_counts} WHERE entity_id = :id', [
            ':id' => $id
        ])->fetchAssoc();
        return $queryCnt['count'] ?? 0;
    }

    /**
     * Function that obtains image url from fid.
     *
     * @param int $fid
     * @return NULL|NULL|string $path image path
     */
    public function getImageFromFid($fid)
    {
        if (empty($fid))
            return null;
        $file = \Drupal\file\Entity\File::load($fid);
        $path = $file != null ? $file->getFileUri() : null;
        return $path;
    }

    /**
     * Function that obtains file_uri from a node and field.
     *
     * @param int $nid
     * @param
     *            string fieldImage
     * @return string $fieldImage
     */
    public function getFileUri($nid, $fieldImage)
    {
        if (empty($nid))
            return '';
        $node = \Drupal\node\Entity\Node::load($nid);
        if (! $node)
            return '';

        if (! $node->hasField($fieldImage))
            return '';
        $image = $node->get($fieldImage)->getValue();
        $targetId = isset($image[0]) ? $image[0]['target_id'] : NULL;
        if (empty($targetId))
            return '';

        // load file
        $file = \Drupal\file\Entity\File::load($targetId);
        if (! is_object($file) || ! method_exists($file, 'getFileUri'))
            return '';
        return $file->getFileUri();
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
    public function getUserPicto($title, $uid = false)
    {
        $defaultUserImg = 'public://avatar_selection/anonyme.jpg';
        if (empty($title))
            return $defaultUserImg;
        // ! $title instanceof Drupal\Core\Render\Markup
        $title = strtoupper($title->__toString());
        $title = trim($title);
        $query = \Drupal::database()->select('users_field_data', 'ufd');
        $query->addField('ufd', 'uid', 'target_id');
        $query->condition('ufd.name', $title, '=');
        $result = $query->execute()->fetchObject();
        $targetId = $result->target_id ?? '';
        if (empty($targetId))
            return $defaultUserImg;

        // load file
        $file = \Drupal\file\Entity\File::load($targetId);
        if (! is_object($file) || ! method_exists($file, 'getFileUri'))
            return $defaultUserImg;
        return $file->getFileUri();
    }

    /**
     * Function that converts a video field into a image url.
     *
     * @param Field $field
     * @return string url
     */
    public function getVideoThumbnail($field)
    {
        $ret = '';
        if (isset($field['0']['#title']['#uri']))
            $ret = $field['0']['#title']['#uri'];
        return $ret;
    }

    /**
     * Function that obtains Term Taxonomy information
     */
    public function obtainTermInformation($termList = null)
    {
        $result = [];
        if (! $termList)
            return [];
        foreach ($termList->getIterator() as $term) {
            if (! is_object($term) || ! method_exists($term, 'getValue'))
                continue;
            $val = $term->getValue();
            $targetId = $val['target_id'] ?? null;
            if (! $targetId)
                continue;
            $term = \Drupal\taxonomy\Entity\Term::load($targetId);
            if (! $term)
                continue;
            $result[] = [
                'name' => $term->getName(),
                'tid' => $targetId
            ];
        }
        return $result;
    }

    // @todo verifiy update of stars
    /**
     * Function that displays default fivestar widget
     */
    public function defaultFivestarWidget($nid)
    {
        $form = array(
            '#post' => array(),
            '#programmed' => FALSE,
            '#tree' => FALSE,
            '#parents' => array(),
            '#array_parents' => array(),
            '#required' => FALSE,
            '#attributes' => array(),
            '#title_display' => 'before'
        );
        $complete_form = [];
        $form_state = new \Drupal\Core\Form\FormState();
        $buidler = \Drupal::formBuilder();
        $form_state->setValues([]);
        $form_state->setProcessInput(FALSE);
        $form_state->setCompleteForm($complete_form);
        $widget = [
            "name" => "basic",
            "css" => "modules/contrib/fivestar/widgets/basic/basic.css"
        ];
        $form['vote'] = [
            '#type' => 'fivestar',
            '#auto_submit' => FALSE,
            '#stars' => 5,
            '#allow_clear' => 0,
            '#allow_revote' => 1,
            '#allow_ownvote' => 1,
            '#widget' => $widget,
            '#default_value' => 0
        ];

        $buidler->doBuildForm('fivestar_preview', $form, $form_state);
        $output = $form;
        $output['#prefix'] = '<div class="fivestar-star-preview fivestar-basic>';
        $output['#suffix'] = '</div>';

        return \Drupal::service('renderer')->render($output);
    }

    /**
     * Function that converts a video field into a video url.
     *
     * @param Field $field
     * @param Boolean $youTubeEmbed
     * @return string url
     */
    public function getVideoUrl($field, $youTubeEmbed = false)
    {
        $ret = '';
        $isRouted = false;
        if (is_array($field) && isset($field['0']['#url'])) {
            $url = $field['0']['#url'];
            $isRouted = $url->isRouted();
            $ret = $isRouted ? Url::fromRoute($url->getRouteName(), $url->getRouteParameters(), $url->getOptions())->toString() : $field['0']['#url']->getUri();
        }
        // @todo case of content,instead of link
        if ($youTubeEmbed && ! $isRouted) {
            $ret = str_replace('watch', 'embed', $ret);
        }
        return $ret;
    }

    /**
     * Function that converts a video field into a video url.
     *
     * @param String $fileUri
     *            Location of image
     * @param String $imageStyle
     *            responsive image style
     * @return responsive_image
     */
    public function getResponsiveImage($fileUri, $imageStyle = 'narrow')
    {

        // The image.factory service will check if our image is valid.
        $image = \Drupal::service('image.factory')->get($fileUri);
        if ($image->isValid()) {
            $width = $image->getWidth();
            $height = $image->getHeight();
        } else {
            return null;
        }
        $image = [
            '#theme' => 'responsive_image',
            '#width' => $width,
            '#height' => $height,
            '#responsive_image_style_id' => $imageStyle,
            '#uri' => $fileUri
        ];
        // Return the render array as block content.
        return $image;
    }

    /**
     * Function that load a block if it exists
     *
     * @param int/string $block_id
     *            identifiant of block
     * @param
     *            boolean is block content
     * @param boolean $renderContent
     * @return block
     */
    public function displayBlock($block_id, $isBlockContent = false, $renderContent = false)
    {
        $block = '';
        $block = $isBlockContent ? \Drupal\block_content\Entity\BlockContent::load($block_id) : \Drupal\block\Entity\Block::load($block_id);
        if (empty($block))
            return '';
        $block = $isBlockContent ? \Drupal::entityManager()->getViewBuilder('block_content')->view($block) : \Drupal::entityManager()->getViewBuilder('block')->view($block);
        return $renderContent ? drupal_render($block) : $block;
    }

    /**
     * Function that dispaly the bilan gratuit form
     *
     * @return form block
     */
    public function displayBilanGratuit()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\mtc_core\Form\BilanGratuitForm');
        return $form;
    }

    /**
     * Function that load a menu
     *
     * @param string $menu
     * @return menutree
     */
    public function displayMenu($menu_id)
    {
        $menu_tree = \Drupal::menuTree();
        $menu_name = 'main';
        // Build the typical default set of menu tree parameters.
        $parameters = new \Drupal\Core\Menu\MenuTreeParameters();
        $parameters->setMaxDepth(2);
        $parameters->setMinDepth(1);
        // Load the tree based on this set of parameters.
        $tree = $menu_tree->load($menu_name, $parameters);

        // Transform the tree using the manipulators you want.
        $manipulators = [
            // Only show links that are accessible for the current user.
            [
                'callable' => 'menu.default_tree_manipulators:checkAccess'
            ],
            // Use the default sorting of menu links.
            [
                'callable' => 'menu.default_tree_manipulators:generateIndexAndSort'
            ]
        ];
        $tree = $menu_tree->transform($tree, $manipulators);
        // Finally, build a renderable array from the transformed tree.
        $menu = $menu_tree->build($tree);

        return $menu['#items'];
    }

    /**
     * Function that load a taxonmy tree
     *
     * @param string $vocabularyId
     * @param string $depth
     * @return menutree
     */
    public function displayTaxonomy($vocabularyId, $depth = 1)
    {
        if (empty($vocabularyId))
            return;
        $tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vocabularyId, 0, $depth);
        return $tree;
    }
    /**
     * Function that strips html tags...
     *
     * @param string $string
     *            string to strip
     * @param boolean $allowEntity
     * @return block
     */
    public function stripTagsAllowEntity($str, $allowEntity = false)
    {
        if ($str instanceof \Drupal\Core\Render\Markup)
            $str = $str->__toString();
        if (! is_string($str))
            return '';
        if ($allowEntity)
            return strip_tags($str);
        return Html::decodeEntities(strip_tags($str));
    }

    /**
     * Function that return a view with template,title
     *
     * @param string $name
     *            name of view
     * @param string $display_id
     *            display_id
     * @return view
     */
    public function drupalViewWithTitle($name, $display_id = 'default')
    {
        $view = \Drupal\views\Views::getView($name);
        if (! $view || ! $view->access($display_id)) {
            return null;
        }
        // Set the display machine id.
        $view->setDisplay($display_id);
        // Get the title.
        $title = $view->getTitle();
        // Render.
        $render = $view->render();
        return [
            'title' => $title,
            'content' => $render
        ];
    }

    /**
     * Displays the taxonomy humour image from field.
     *
     * @param
     *            field Drupal\Core\Field\EntityReferenceFieldItemList
     *
     * @return string html
     *
     */
    public function displayHumour($field)
    {
        $imageToRender = '';
        $humours = $field->getIterator();
        foreach ($humours as $humour) {
            $targetId = $humour->getValue();
            $targetId = $targetId['target_id'];
            $term = \Drupal\taxonomy\Entity\Term::load($targetId);
            $image = $term->get('field_image')->getValue();
            if (! $image)
                continue;
            $image = $image[0];
            $file = \Drupal\file\Entity\File::load($image['target_id']);
            // The image.factory service will check if our image is valid.
            $image = \Drupal::service('image.factory')->get($file->getFileUri());
            if ($image->isValid()) {
                $variables['width'] = $image->getWidth();
                $variables['height'] = $image->getHeight();
            } else {
                $variables['width'] = $variables['height'] = NULL;
            }
            $imageToRender = [
                '#theme' => 'image_style',
                '#width' => $variables['width'],
                '#height' => $variables['height'],
                '#style_name' => 'thumbnail',
                '#uri' => $file->getFileUri(),
                '#title' => $term->getName()
            ];
        }
        return $imageToRender;
    }

    /**
     * Converts french time duration to minutes.
     *
     * @param
     *            string time
     * @return int minutes
     *
     */
    public function convertTimeToMinutes($time)
    {
        if (empty($time))
            return $time;

        preg_match("/(\d+)(\s)*(heures|heure|h)/", $time, $results);
        $hours = $results[1] ?? 0;
        preg_match("/(\d+)(\s)*(minutes|minute|min|mins|mn)(.*)/", $time, $results);
        $mins = $results[1] ?? 0;
        $minutes = ($hours * 60) + $mins;
        if ($minutes == 0)
            return $time;
        return $minutes;
    }

    /**
     * Gets a unique identifier for this Twig extension.
     *
     * @return string A unique identifier for this Twig extension.
     */
    public function getName()
    {
        return 'mtc_core.twig_extension';
    }
}
