<?php

namespace Drupal\mtc_core\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\message\Entity\Message;
use Drupal\message\MessageInterface;
use Drupal\user\UserInterface;

class LcReplyFormAjaxController {

  public static function ajaxReply(UserInterface $user, $top, MessageInterface $message) {
    $subject = \Drupal::request()->query->get('subject') ?? '';
    $mess = Message::create([
      'template' => 'private_message'
    ]);

    if (!empty($message) && \Drupal::currentUser()->id() == $message->getOwnerId()) {
      if ($message->hasField('field_message_private_to_user') && !empty($message->get('field_message_private_to_user'))) {
        $user = $message->get('field_message_private_to_user')->referencedEntities()[0];
      }
    }

    $mess->set('field_message_private_to_user', [$user]);
    $mess->set('field_message_private_subject', $subject);
    $form = \Drupal::service('entity.form_builder')->getForm($mess);
    $ajax_response = new AjaxResponse();
    if($top == 1) {
      $ajax_response->addCommand(new HtmlCommand('#reply_form_private_message_top', $form));
    }
    else {
      $ajax_response->addCommand(new HtmlCommand('#reply_form_private_message', $form));
    }
    return $ajax_response;
  }

}
