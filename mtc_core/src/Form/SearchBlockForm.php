<?php
namespace Drupal\mtc_core\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\search\SearchPageRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the search form for the search block.
 */
class SearchBlockForm extends FormBase {

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * The renderer.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected $renderer;

    /**
     * Constructs a new SearchBlockForm.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *            The config factory.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *            The renderer.
     */
    public function __construct(ConfigFactoryInterface $config_factory, RendererInterface $renderer)
    {
        $this->configFactory = $config_factory;
        $this->renderer = $renderer;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function create(ContainerInterface $container)
    {
        return new static($container->get('config.factory'), $container->get('renderer'));
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return 'search_block_form';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $taxonomyTerm = \Drupal::routeMatch()->getParameter('taxonomy_term');
        $placeHolder = 'Rechercher dans le site';
        // modify placeholder in case of taxonomy mag
        if (isset($taxonomyTerm) && $taxonomyTerm->getVocabularyId() == 'theme') {
            $placeHolder = 'Rechercher dans le Mag';
            $form['filter_theme[]'] = [
                '#type' => 'hidden',
                '#value' => $taxonomyTerm->id()
            ];
        }
        $form['#action'] = '/recherche';
        $form['#method'] = 'GET';

        $form['query'] = [
            '#type' => 'search',
            '#title' => '',
            '#size' => 15,
            '#attributes' => [
                'placeholder' => $placeHolder
            ],
            '#default_value' => ''
        ];
        $form['actions'] = [
            '#type' => 'actions'
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Search'),
            '#name' => ''
        ];
        return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // This form submits to the search page, so processing happens there.
    }
}
