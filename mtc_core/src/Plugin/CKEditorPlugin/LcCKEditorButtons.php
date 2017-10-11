<?php

namespace Drupal\mtc_core\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "mtc_core ckeditor button" plugin.
 *
 * NOTE: The plugin ID ('id' key) corresponds to the CKEditor plugin name.
 * It is the first argument of the CKEDITOR.plugins.add() function in the
 * plugin.js file.
 *
 * @CKEditorPlugin(
 *   id = "lcckeditorbuttons",
 *   label = @Translation("MTC Core ckeditor buttons")
 * )
 */
class LcCKEditorButtons extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   *
   * NOTE: The keys of the returned array corresponds to the CKEditor button
   * names. They are the first argument of the editor.ui.addButton() or
   * editor.ui.addRichCombo() functions in the plugin.js file.
   */
  public function getButtons() {
    // Make sure that the path to the image matches the file structure of
    // the CKEditor plugin you are implementing.
    return [
      'widgetEncadreRedac' => [
        'label' => t('Inserer box encadrer redactionnelle'),
        'image' => 'modules/custom/mtc_core/js/plugins/MtcCoreCkeditorButtons/images/encadre-redac.jpg',
      ],
     'widgetEncadreConnexe' => [
        'label' => t('Inserer box connexe'),
        'image' => 'modules/custom/mtc_core/js/plugins/MtcCoreCkeditorButtons/images/encadre-connexe.jpg',
       ],
     'widgetEncadrePromo' => [
            'label' => t('Inserer box promotionnelle'),
            'image' => 'modules/custom/mtc_core/js/plugins/MtcCoreCkeditorButtons/images/encadre-promo.jpg',
       ],
      'widgetMiseEnAvant' => [
            'label' => t('Inserer une mise avant'),
            'image' => 'modules/custom/mtc_core/js/plugins/MtcCoreCkeditorButtons/images/encadre-miseenavant.jpg',
       ],
      'widgetbootstrap2EqualCol' => [
            'label' => t('Add 2: 50 %column box'),
            'image' => 'modules/custom/mtc_core/js/plugins/MtcCoreCkeditorButtons/images/bootstrap-2-col.jpg',
        ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    // Make sure that the path to the plugin.js matches the file structure of
    // the CKEditor plugin you are implementing.
    return drupal_get_path('module', 'mtc_core') . '/js/plugins/MtcCoreCkeditorButtons/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
