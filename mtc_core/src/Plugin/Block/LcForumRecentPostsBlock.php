<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Provides a 'LcForumRecentPostsBlock' block.
 *
 * @Block(
 * id = "lc_forum_recent_posts_block",
 * admin_label = @Translation("MTC Core forum recent posts block"),
 * category = @Translation("MTC Core Custom block")
 * )
 */
class LcForumRecentPostsBlock extends BlockBase implements ContainerFactoryPluginInterface {

    /*
     * Default Number of items
     * @var Interger
     */
    const LIMIT_COMMENT = 3;

    /**
     * Drupal\Core\Database\Driver\mysql\Connection definition.
     *
     * @var \Drupal\Core\Database\Driver\mysql\Connection
     */
    protected $database;

    /**
     * Construct.
     *
     * @param array $configuration
     *            A configuration array containing information about the plugin instance.
     * @param string $plugin_id
     *            The plugin_id for the plugin instance.
     * @param string $plugin_definition
     *            The plugin implementation definition.
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->database = $database;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static($configuration, $plugin_id, $plugin_definition, $container->get('database'));
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function defaultConfiguration()
    {
        return [
            'number_of_posts' => $this->t('3'),
            'introduction' => $this->t('')
        ] + parent::defaultConfiguration();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $introduction = $this->configuration['introduction'];
        $introduction = is_array($introduction) ? $introduction['value'] : $introduction->__toString();
        $form['number_of_posts'] = [
            '#type' => 'number',
            '#title' => $this->t('Nombre de posts'),
            '#description' => $this->t('Nombre de posts à afficher'),
            '#default_value' => $this->configuration['number_of_posts'],
            '#weight' => '3'
        ];
        $form['introduction'] = [
            '#type' => 'text_format',
            '#title' => $this->t('Chapeau'),
            '#description' => $this->t('Text d&#x27;introduction'),
            '#default_value' => $introduction,
            '#weight' => '2'
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
        $this->configuration['number_of_posts'] = $form_state->getValue('number_of_posts');
        $this->configuration['introduction'] = $form_state->getValue('introduction');
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build = [];
        $build['introduction'] = $this->configuration['introduction'];
        $build['comments'] = $this->recentForumPosts();
        return $build;
    }

    /*
     * Function that obtains recent posts
     *
     */
    public function recentForumPosts()
    {
        $limit = (int) $this->configuration['number_of_posts'] ?? self::LIMIT_COMMENT;
        $query = $this->database->select('comment_field_data', 'comment');

        $query->fields('comment', [
            'cid',
            'name',
            'changed',
            'subject',
            'entity_id'
        ]);
        $query->fields('taxonomy', [
            'name',
            'tid'
        ]);
        $query->fields('file', [
            'uri'
        ]);

        $query->leftJoin('user__user_picture', 'user_picture', 'user_picture.entity_id = comment.uid');
        $query->leftJoin('file_managed', 'file', 'file.fid = user_picture.user_picture_target_id');
        $query->leftJoin('forum', 'frm', 'frm.nid = comment.entity_id');
        $query->leftJoin('taxonomy_term_field_data', 'taxonomy', 'taxonomy.tid = frm.tid');
        $query->condition('comment.field_name', 'comment_forum');
        $query->range(0, $limit);
        $query->orderby('comment.changed', 'DESC');
        return $query->execute()->fetchAllAssoc('cid');
    }
}
