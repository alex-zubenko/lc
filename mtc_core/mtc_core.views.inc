<?php

/**
 * @file
 * Contains mtc_core module views instructions/
 */

/**
 * Implements hook_views_data_alter().
 */
function mtc_core_views_data_alter(&$data) {
  $data['message']['table']['join']['mc_message_read'] = [
    'field' => 'mid',
    'left_field' => 'mid',
    'type' => 'INNER',
  ];


  $data['message']['new_relationship'] = array(
    'title' => t('New message relationship'),
    'help' => t('New message relationship'),

    'relationship' => array(
      'base' => 'mc_message_read',
      'base field' => 'mid',
      // 'unique_dummy_name'.
      'field' => 'mid',
      'id' => 'standard',
      'label' => t('New message relationship'),
    ),
  );



}

function mtc_core_views_data() {
  $data = [];
  $data['mc_message_read'] = [
    'table' => [
      'provider' => 'mtc_core',
      'base' => [
        'field' => 'mid',
        'title' => t('Message read table'),
        'help' => t('Describes Message read table.'),
        'weight' => -10,
      ],
      'group' => t('Message read'),
      'join' => [
        'field' => 'mid',
        'left_field' => 'mid',
        'type' => 'INNER',
      ]
    ],

  ];
  $data['mc_message_read']['is_new'] = [
    'title' => t('New message'),
    'help' => t('New message field.'),
    'field' => array(
      'id' => 'boolean',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('New message'),
      'type' => 'yes-no',
      'use_equal' => TRUE,
    ),
  ];
  return $data;
}
