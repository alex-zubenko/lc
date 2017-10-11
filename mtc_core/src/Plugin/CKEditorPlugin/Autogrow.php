<?php

namespace Drupal\mtc_core\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginBase;
use Drupal\editor\Entity\Editor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the "autogrow" plugin.
 *
 * @CKEditorPlugin(
 *   id = "autogrow",
 *   label = @Translation("CKEditor Autogrow"),
 *   module = "mtc_core"
 * )
 */
class Autogrow extends CKEditorPluginBase implements CKEditorPluginContextualInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The base url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    PluginBase::__construct($configuration, $plugin_id, $plugin_definition);

    $this->baseUrl = $request_stack->getCurrentRequest()->getBaseUrl();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  function getFile() {
    return $this->baseUrl . '/libraries/ckeditor/plugins/autogrow/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor): array {
    return [
      'autoGrow_onStartup' => TRUE,
      'autoGrow_minHeight' => 75,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  function isEnabled(Editor $editor) {
    return TRUE;
  }

}
