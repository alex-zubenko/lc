<?php
namespace Drupal\linecoaching_forum_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Class LcForumNotificationForm.
 *
 * @package Drupal\linecoaching_forum_notification\Form
 */
class LcForumNotificationForm extends FormBase {

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return 'lc_forum_notification_form';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      $config = \Drupal::configFactory()->get('linecoaching_forum_notification.settings');
      $subject_template = $config->get('subject_template');
      $message_template = $config->get('message_template');

      $form['subject_template'] = [
        '#type' => 'textarea',
        '#title' => t('Subject'),
        '#default_value' => $subject_template,
        '#required' => TRUE,
      ];
      $form['message_template'] = [
        '#type' => 'textarea',
        '#default_value' => $message_template,
        '#title' => t('Message'),
        '#required' => TRUE,
      ];
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node', 'user'],
      ];
      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );

      return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

      $message_template  = $form_state->getValue('message_template');
      $subject_template  = $form_state->getValue('subject_template');
      \Drupal::configFactory()->getEditable('linecoaching_forum_notification.settings')
        ->set('subject_template', $subject_template)
        ->set('message_template', $message_template)
        ->save();
    }
}
