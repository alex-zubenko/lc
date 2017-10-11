<?php
namespace Drupal\linecoaching_forum_notification\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;


/**
 * Class LcUnfollowSubmit.
 *
 * @package Drupal\linecoaching_forum_notification\Form
 */
class LcForumPostFlagUnfollow extends FormBase {

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return '_lc_forum_post_flag_unfollow';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
      $form['unfollow_topics'] = [
        '#type' => 'hidden',
        '#attributes' => [
          'class' => [
            'unfollow-topic-values'
          ]
        ]
      ];
      $form['submit'] = array(
        '#type' => 'submit',
        '#ajax' => array(
          'callback' => '::fakeSubmit',
        ),
        '#value' => t('Ne plus suivre'),
      );

      return $form;
    }

    public function fakeSubmit(array $form, FormStateInterface $form_state) {
      $user_input = $form_state->getUserInput();
      $values = explode(',', $user_input['unfollow_topics']);

      $flag_service = \Drupal::service('flag');
      $flag = $flag_service->getFlagById('following_forum_post');

      if (!empty($values)) {
        foreach ($values as $value) {
          $entity = Node::load($value);
          $flag_service->unflag($flag, $entity);
        }
      }else {

      }
      $response = new AjaxResponse();
      $response->addCommand(new InvokeCommand('a[href="/forum/users-followers"]','click'));
      return $response;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {


    }
}
