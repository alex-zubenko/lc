<?php

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * @param $form
 *   The form.
 * @param $form_state
 *   The form state.
 */
function linecoaching_theme_form_system_theme_settings_alter(&$form, &$form_state)
{
    $form['node_tid_nids'] = [
        '#type' => 'details',
        '#title' => t('Node/Term nids ou tids configuration')
    ];
    $form['node_tid_nids']['abc_regime_tid'] = [
        '#type' => 'number',
        '#description' => t('Le tid de Abc regimes - taxonominie'),
        '#default_value' => theme_get_setting('abc_regime_tid', 'linecoaching_theme')
    ];
    $form['node_tid_nids']['diaporama_tid'] = [
        '#type' => 'number',
        '#description' => t('Le tid de diaporama - taxonominie'),
        '#default_value' => theme_get_setting('diaporama_tid', 'linecoaching_theme')
    ];
    $form['node_tid_nids']['video_tid'] = [
        '#type' => 'number',
        '#description' => t('Le tid de video - taxonominie'),
        '#default_value' => theme_get_setting('video_tid', 'linecoaching_theme')
    ];
    $form['links'] = [
        '#type' => 'details',
        '#title' => t('Ensemble de configuration de lien')
        ];
    $form['links']['bilan_site'] = [
        '#type' => 'url',
        '#description' => t('Le lien vers le bilan gratuit'),
        '#default_value' => theme_get_setting('bilan_site', 'linecoaching_theme')
    ];
    $form['#submit'][] = 'linecoaching_theme_form_system_theme_settings_submit';
}

function linecoaching_theme_form_system_theme_settings_submit(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
}
