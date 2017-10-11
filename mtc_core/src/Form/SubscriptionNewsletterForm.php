<?php
namespace Drupal\mtc_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Class SubscriptionNewsletterForm.
 *
 * @package Drupal\mtc_core\Form
 */
class SubscriptionNewsletterForm extends FormBase
{

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return 'subscription_newsletter_form';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        static $counter = 0;
        if($form_state->getValue('counter')) {
          $counter = $form_state->getValue('counter');
        }
        else {
          $counter++;
        }

        $form['counter'] = [
          '#type' => 'hidden',
          '#default_value' => $counter,
        ];

        $form['email-address-newsletter'] = [
            '#type' => 'textfield',
            '#title' => 'Inscription à la newsletter',
            '#attributes' => [
                'placeholder' => 'Entrez votre adresse email',
                'class' => ['form-email'],
            ],
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Ok'),
          "#attributes" => [
            'onscroll' => "return false",
          ],
            '#ajax' => [
                'callback' => [
                    $this,
                    'subscriber_user_newsletter_ajax_callback'
                ],
                'progress' => [
                    'type' => 'throbber',
                    'message' => '.....'
                ]
            ]
        ];
        $form['submit']['#attributes']['class'][] = "subscription_newsletter_$counter";
        // Add a wrapper for our #ajax callback
        $form['#attributes']['class'][] = "subscription_newsletter_block_wrapper_$counter";
        $form['#prefix'] = '<div id="subscription_newsletter_block_wrapper">';
        $form['#suffix'] = '</div>';
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
        return $form;
    }





    /**
     * Ajax callback function for subscriber_user_newsletter_block submit button
     */
    function subscriber_user_newsletter_ajax_callback($form, FormStateInterface $form_state)
    {
        $counter = $form_state->getValue('counter');
        $selector = ".subscription_newsletter_block_wrapper_{$counter}  .form-item-email-address-newsletter";
        $response = new AjaxResponse();

        $renderer = \Drupal::service('renderer');
        $messages = [
            '#type' => 'status_messages',
            '#attributes' => [
              'class' => 'status-message',
            ],
        ];
        $messages = $renderer->renderRoot($messages);
        $response->addCommand(new Ajax\AppendCommand($selector, $messages));

        $mail = $form_state->getValue('email-address-newsletter');
        $status = \Drupal::service('mtc_core.a7_manager')->addNewsletterSubscription($mail);

        $message = '';
        if ($status['status']) {
          $message = 'Merci pour votre inscription';
          $response->addCommand(new Ajax\OpenModalDialogCommand('', $message, ['width' => '25%', 'dialogClass' => 'newsletter-a7_manager']));
        }

      return $response;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      $mail = $form_state->getValue('email-address-newsletter');
      if (empty($mail)) {
          $form_state->setErrorByName('email-address-newsletter', 'Adresse e-mail vide');
      }
      elseif (! \Drupal::service('email.validator')->isValid($mail)) {
          $form_state->setErrorByName('email-address-newsletter', 'Votre adresse email n\'est pas conforme (ex: exemple@domain.com)');
      }
      else {
        $host = explode('@', $mail);
        $host = $host[1];
        if ($socket = @fsockopen($host, 80, $errno, $errstr, 7)) {
          fclose($socket);
        }
        else {
          $form_state->setErrorByName('email-address-newsletter', "Bad domain ($host) in email address.");
        }
      }
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state){}
}
