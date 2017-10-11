<?php

namespace Drupal\mtc_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\message\Entity\Message;

/**
 * Class LcUserMessageBulkOperation.
 *
 * @package Drupal\mtc_core\Form
 */
class LcUserMessageBulkOperation extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'lc_message_bulk_operation';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      $option = [
        0 => t('No option'),
        1 => t('Delete'),
        2 => t('Lus'),
        3 => t('Non lus'),
      ];
      $route_name = \Drupal::routeMatch()->getRouteName();
      if ($route_name == 'lc.user.sentMessages') {
        $option = [
          0 => t('No option'),
          1 => t('Delete'),
        ];
      }
      $form['all_massages'] = [
        '#type' => 'checkbox',
        '#title' => t('Select all messages?'),
        '#default_value' => FALSE,
      ];
      $form['operation'] = [
        '#type' => 'select',
        '#title' => $this->t('View mode'),
        '#options' => $option,
        '#default_value' => 0,
        '#attributes' => [
          'data-drupal-autosubmit' => TRUE,
          'class' => ['all-private-bulk-massages'],
        ]
      ];
      $form['selected_message'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'class' => [
            'selected-message-values'
          ]
        ]
      ];
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );

      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
      $input_data = $form_state->getUserInput();

      $selected_messages = $input_data['selected_message'];
      if(!empty($selected_messages)) {
        $selected_messages = trim($selected_messages, ',');
        foreach (explode(',',$selected_messages) as $selected_message) {
          $message = Message::load($selected_message);

          switch ($input_data['operation']) {
            case 3:
              $query = \Drupal::database()
                ->update('mc_message_read')
                ->fields([
                  'is_new' => 1,
                ])
                ->condition('mid', $selected_message)
                ->execute();
              break;
            case 2:
              $query = \Drupal::database()
                ->update('mc_message_read')
                ->fields([
                  'is_new' => 0,
                ])
                ->condition('mid', $selected_message)
                ->execute();
              break;
            case 1:
              $message->delete();
              break;
          }
        }
      }

    }
}
