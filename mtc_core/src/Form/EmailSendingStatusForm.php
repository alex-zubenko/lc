<?php

namespace Drupal\mtc_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class EmailSendingStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_email__private_email_sending_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default_value = User::load(\Drupal::currentUser()->id())->get('field_receive_email')->value;
    $form['status_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Recevoir une notification par courrier pour des messages privés entrants'),
      '#default_value' => $default_value,
      '#weight' => -3,
    ];
    $form['user_id'] = [
      '#type' => 'hidden',
      '#value' => \Drupal::currentUser()->id(),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();

    $user = User::load($user_input['user_id']);
    $user->set('field_receive_email', $user_input['status_mail']);
    $user->save();
  }
}
