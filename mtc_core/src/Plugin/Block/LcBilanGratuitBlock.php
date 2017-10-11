<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'LcBilanGratuitBlock' block.
 *
 * @Block(
 * id = "lc_bilan_gratuit_block",
 * admin_label = @Translation("Lc bilan gratuit block"),
 * category = @Translation("MTC Core Custom block")
 * )
 */
class LcBilanGratuitBlock extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * Drupal\Core\Form\FormBuilder definition.
     *
     * @var \Drupal\Core\Form\FormBuilder
     */
    protected $formBuilder;

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
    public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilder $form_builder)
    {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->formBuilder = $form_builder;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        return new static($configuration, $plugin_id, $plugin_definition, $container->get('form_builder'));
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $intro_value = isset($this->configuration['form_introduction_text']['value']) ? $this->configuration['form_introduction_text']['value'] : 'Je détermine gratuitement mon Profil de Comportement Alimentaire ®';
        $form['form_introduction_text'] = [
            '#type' => 'text_format',
            '#format' => 'full_html',
            '#title' => $this->t('Introduction text'),
            '#description' => $this->t('Introduction text'),
            '#default_value' => $intro_value,
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
        $this->configuration['form_introduction_text'] = $form_state->getValue('form_introduction_text');
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        // @todo ,replace introduction text
        $form = $this->formBuilder->getForm('Drupal\mtc_core\Form\BilanGratuitForm');
        $userRoles = \Drupal::currentUser()->getRoles(true);
        $node = \Drupal::routeMatch()->getParameter('node');
        $routeName = \Drupal::routeMatch()->getRouteName();
        if ($node !== null && $node->getType() == 'landing') {
            return $form;
        }
        // display block based on role
        if (in_array('utilisateur_abonne', $userRoles) ||($routeName == 'mtc_core.subscriber.home.program')) {
            return [];
        }
        return $form;
    }
}
