<?php

namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'LcSubscriptionNewsletterBlock' block.
 *
 * @Block(
 *  id = "lc_subscription_newsletter_footer_block",
 *  admin_label = @Translation("mtc_core subscription newsletter Footer block"),
 *  category = @Translation("Mtc Core Custom block")
 * )
 */
class LcSubscriptionNewsletterFooterBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        FormBuilder $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }



  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $newsLetterForm = $this->formBuilder->getForm('Drupal\mtc_core\Form\SubscriptionNewsletterForm');
    $build['item']['newsletter'] = $newsLetterForm;
    return $build;
  }

}
