<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\og\Og;
use Drupal\Component\Utility\Html;
use Drupal\image\Entity\ImageStyle;

/**
 * Class LcForumController.
 *
 * @package Drupal\mtc_core\Controller
 */
class LcUserContentController extends ControllerBase
{
    /**
     * Methode that sends displays form adding private message.
     *
     * @param
     *            \Drupal\user\Entity\User recepient of the mail to send
     *            Return json array of comments.
     */
    public function addPrivateMessage($user)
    {
        $recepientUser = \Drupal\user\Entity\User::load($user);
        $redirectUrl = \Drupal::request()->query->get('destination');
        $subject = \Drupal::request()->query->get('subject') ?? '';
        // redirect if user not found
        if (! $recepientUser && ! empty($redirectUrl)) {
            return new RedirectResponse($redirectUrl);
        }
        if (! $recepientUser) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $message = Message::create([
            'template' => 'private_message'
        ]);
        $message->set('field_message_private_to_user', [
            $recepientUser
        ]);
        $form = $this->entityFormBuilder()->getForm($message);

        $name = $recepientUser->getAccountName();
        $defaultToUserVal = $name . ' (' . $recepientUser->id() . ')';

        $form['message_profile']['field_message_private_subject']['widget'][0]['value']['#title'] = 'Sujet';
        $form['message_profile']['field_message_private_subject']['widget'][0]['value']['#value'] = $subject;
        $redirectUrl = $redirectUrl ?? '/';
        $form['actions']['cancel'] = [
            '#markup' => '<a class="btn" href="' . $redirectUrl . '" title="Annuler">Annuler</a>',
            '#weight' => 100
        ];
        return $form;
    }

    /**
     * Function that returns list of friends of current user
     *
     * @return html
     */
    public function listMyFriends()
    {
        // display 25 friends per page
        // get list of friends
        $numPerPage = 25;
        $currentUid = \Drupal::currentUser()->id();
        $db = \Drupal::database();
        // get total count for pagination purposes
        $query = $db->select('flagging', 'f');
        $query->fields('f', [
            'entity_id'
        ]);
        $query->condition('f.entity_type', 'user', '=');
        $query->condition('f.flag_id', 'friend_list', '=');
        $query->leftJoin('users_field_data', 'ufd', 'ufd.uid = f.entity_id');
        $query->condition('ufd.status', 1, '=');
        $total = $query->condition('f.uid', $currentUid, '=')
            ->countQuery()
            ->execute()
            ->fetchField();
        // pager
        $page = pager_default_initialize($total, $numPerPage);
        $offset = $numPerPage * $page;

        $query = $db->select('flagging', 'f');
        $query->addField('f', 'entity_id', 'uid');
        $query->addField('ufd', 'name');
        $query->addExpression(':request_time', 'request_time', array(
            ':request_time' => REQUEST_TIME
        ));
        $query->addField('fm', 'uri');
        $query->addField('ufd', 'access');
        $query->leftJoin('users_field_data', 'ufd', 'ufd.uid = f.entity_id');
        $query->leftJoin('user__user_picture', 'uup', 'uup.entity_id = f.uid');
        $query->leftJoin('file_managed', 'fm', 'fm.fid = uup.user_picture_target_id');
        $query->condition('f.entity_type', 'user', '=');
        $query->condition('f.flag_id', 'friend_list', '=');
        $query->condition('f.uid', $currentUid, '=');
        $query->condition('ufd.status', 1, '=');
        $query->range($offset, $numPerPage);
        $result = $query->execute()->fetchAll();
        $render = [
            '#theme' => 'mtc_core_my_friend_list',
            '#content' => null
        ];
        if ($result) {
            $render = [];
            $render[] = [
                '#theme' => 'mtc_core_my_friend_list',
                '#content' => $result
            ];
            // add the pager.
            $render[] = [
                '#type' => 'pager'
            ];
        }
        return $render;
    }

    /**
     *
     * @return string[]|\Drupal\og\Entity\OgMembership[][]|\Drupal\Core\Entity\EntityInterface[][]
     */
    public function ogGroupChatGroupHome()
    {
        $currentUser = \Drupal::currentUser();
        $memberList = $unMemberList = [];
        // obtain list of chat groups
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'chat_groups');
        $query->condition('status', 1);
        $query->range(0, 10);
        $query->sort('changed', 'DESC');
        $chatGrpNids = $query->execute();
        $chatGroups = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($chatGrpNids);
        // get memberships of user
        $memberShips = Og::getMemberships($currentUser);
        $memberGroupNids = [];
        foreach ($memberShips as $member) {
            $memberGroupNids[] = $member->get('entity_id')->value;
        }

        foreach ($chatGroups as $chatGroup) {
            $data = [];
            $data['is_member'] = false;
            if (in_array($chatGroup->id(), $memberGroupNids)) {
                $data['is_member'] = true;
            }
            $data['group_title'] = $chatGroup->getTitle();
            $data['group_nid'] = $chatGroup->id();
            $memberList[] = $data['is_member'] == true ? $data : [];
            $unMemberList[] = $data['is_member'] == false ? $data : [];
        }
        return [
            '#theme' => 'mtc_core_og_chat_group',
            '#memberList' => array_filter($memberList),
            '#unMemberList' => array_filter($unMemberList)
        ];
    }

    /**
     * Function thtat subscribes and unsubscribes use
     *
     * @param string $type
     * @param int $nid
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function ogGroupChatGroupSubscription($type, $nid)
    {
        if (empty($nid) || empty($type)) {
            return $this->redirect('mtc_core.og_group_chat_group_home');
        }
        $currentUser = \Drupal::currentUser();
        $roles = $currentUser->getRoles();
        $allowedRoles = [
            'utilisateur_abonne',
            'administrator',
            'votre_coach',
            'forum_moderator',
            'chat_moderator',
            'chat_manager'
        ];
        if (! array_intersect($allowedRoles, $roles)) {
            drupal_set_message('Vous devrez être abonné!', 'warning');
            return $this->redirect('mtc_core.og_group_chat_group_home');
        }

        $group = \Drupal\node\Entity\Node::load($nid);
        switch ($type) {
            case 'subscribe':
                if ($membership = Og::getMembership($group, $currentUser, [
                    'blocked',
                    'pending',
                    'default'
                ])) {
                    $membership->set('state', 'active');
                    $membership->save();
                    break;
                }
                $membership = Og::createMembership($group, $currentUser);
                $membership->set('state', 'active');
                $membership->save();
                break;
            case 'unsubscribe':

                if ($membership = Og::getMembership($group, $currentUser, [
                    'active',
                    'pending',
                    'default'
                ])) {
                    $membership->set('state', 'blocked');
                    $membership->save();
                }
                break;
            default:
                break;
        }
        return $this->redirect('mtc_core.og_group_chat_group_home');
    }

    /**
     * Function that displays chat nasteo without cache
     *
     * @return string[]|\Drupal\og\Entity\OgMembership[][]|\Drupal\Core\Entity\EntityInterface[][]
     */
    public function chatNasteo()
    {
        \Drupal::service('page_cache_kill_switch')->trigger();

        // obtain config
        $config = \Drupal::configFactory()->getEditable('mtc_core.chatnasteo.settings');

        $salonNum = $config->get('chatnasteo.salon');
        $iframe = true;
        if (empty($salonNum) || ! is_numeric($salonNum))
            $iframe = null;
            // get pseudo
            $currentUser = \Drupal::currentUser();
            $roles = $currentUser->getRoles();
            if ($currentUser->isAnonymous())
                $iframe = null;
                // check roles
                if (! array_intersect($roles, [
                    'administrator',
                    'utilisateur_abonne'
                ])) {
                    $iframe = null;
                }
                $pseudo = urlencode($currentUser->getDisplayName());
                $api_key = '3-lvjfetetjvjrvdjktt';
                $api_url = 'http://api.nasteolink.net/api_ident_set.php?auth=' . $api_key . '&p=' . $pseudo . '&t=' . time();
                $uniqid = file_get_contents($api_url);
                if (in_array($uniqid, [
                    '-1',
                    '-2'
                ])) {
                    $iframe = null;
                }
                if ($iframe) {
                    $iframe = '<iframe id="chat"
                name="nasteoLink"
                src="//salon.nasteolink.net/?l=3&s=' . $salonNum . '&u=' . $uniqid . '&t=' . time() . '"
                  style="width:100%;height:500px">
            </iframe>';
                }
                // get content
                $title = $config->get('chatnasteo.title');
                $body = $config->get('chatnasteo.body');
                $body = isset($body) ? $body['value'] : '';
                $photoUri = '';
                $photoFid = $config->get('chatnasteo.photo_expert');
                if ($photoFid && isset($photoFid[0])) {
                    $file = \Drupal\file\Entity\File::load($photoFid[0]);
                    $photoUri = isset($file) ? $file->getFileUri() : '';
                }
                return [
                    '#theme' => 'mtc_core_chat_nasteo',
                    '#iframe' => $iframe,
                    '#content' => compact('title', 'body', 'photoUri')
                ];
    }

    /**
     * Function that groups messages into conversations
     *
     * @param int $message
     *            id de message
     * @return string
     */
    public function privateMessageConversation($mid)
    {
        // remove cache from page
        \Drupal::service('page_cache_kill_switch')->trigger();
        $currentUser = \Drupal::currentUser();
        $message = Message::load($mid);
        $ownerUid = $message->get('uid')
            ->first()
            ->getString();
        $destUid = $message->get('field_message_private_to_user')
            ->first()
            ->getString();
        $subject = $message->get('field_message_private_subject')
            ->first()
            ->getString();
        // /add access restrictions
        if (! in_array($currentUser->id(), [
            $ownerUid,
            $destUid
        ]) && ! in_array($currentUser->getRoles(), [
            'administrator'
        ])) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }

        $destUser = \Drupal\user\Entity\User::load($destUid);
        $ownerUser = \Drupal\user\Entity\User::load($ownerUid);
        // get user info
        $pictoEntity = isset($destUser) ? $destUser->get('user_picture')->entity : null;
        $destUser = [
            'profile_image' => isset($pictoEntity) ? $pictoEntity->url() : file_create_url('public://avatar_selection/anonyme.jpg'),
            'pseudo' => isset($destUser) ? $destUser->getDisplayName() : '',
            'uid' => $destUid
        ];
        $pictoEntity = isset($destUser) ? $ownerUser->get('user_picture')->entity : null;
        $ownerUser = [
            'profile_image' => isset($pictoEntity) ? $pictoEntity->url() : file_create_url('public://avatar_selection/anonyme.jpg'),
            'pseudo' => isset($destUser) ? $ownerUser->getDisplayName() : '',
            'uid' => $ownerUid
        ];

        $participants[$ownerUid] = $ownerUser;
        $participants[$destUid] = $destUser;

        // get messages
        $query = \Drupal::database()->select('message__field_message_private_to_user', 'mfmptu');
        $query->fields('mmr', [
            'is_new',
            'mid'
        ]);
        $query->addField('mfmps', 'field_message_private_subject_value', 'subject');
        $query->addField('mfd', 'created', 'created');
        $query->addField('mfd', 'uid', 'author');
        $query->addField('mfmpb', 'field_message_private_body_value', 'body');
        $query->addField('mfmptu', 'field_message_private_to_user_target_id', 'recipient');
        $query->join('mc_message_read', 'mmr', 'mmr.mid = mfmptu.entity_id');
        $query->join('message__field_message_private_subject', 'mfmps', 'mfmps.entity_id = mfmptu.entity_id');
        $query->join('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfmptu.entity_id');
        $query->join('message_field_data', 'mfd', 'mfd.mid = mfmptu.entity_id');
        $or = db_or();
        $or->condition(db_and()->condition('mfmptu.field_message_private_to_user_target_id', $ownerUid)
            ->condition('mfd.uid', $destUid));
        $or->condition(db_and()->condition('mfmptu.field_message_private_to_user_target_id', $destUid)
            ->condition('mfd.uid', $ownerUid));
        $query->condition($or);
        $query->condition('mfmps.field_message_private_subject_value', $subject);
        $query->orderBy('mfd.created', 'DESC');
        $query->range(0, 100);
        $messages = $query->execute()->fetchAllAssoc('mid');
        return [
            '#theme' => 'mtc_core_private_message_conversation',
            '#data' => compact('messages', 'subject', 'ownerUser', 'participants', 'currentUser', 'message')

        ];
    }

  /**
   * Function that displays sending messages.
   *
   * @return array
   */
    public function sentPrivateMessages($user) {
      // @todo add pager
      // remove cache
      \Drupal::service('page_cache_kill_switch')->trigger();
      $currentUid = \Drupal::currentUser()->id();
      // get received messages
      $query = \Drupal::database()->select('message__field_message_private_to_user', 'mfmptu');
      $query->fields('mmr', [
        'is_new',
        'mid'
      ]);
      $query->addField('mfmps', 'field_message_private_subject_value', 'subject');
      $query->addField('mfd', 'created', 'created');
      $query->addField('mfd', 'uid', 'author');
      $query->addField('mfmpb', 'field_message_private_body_value', 'body');
      $query->addField('mfmptu', 'field_message_private_to_user_target_id', 'recipient');
      $query->join('mc_message_read', 'mmr', 'mmr.mid = mfmptu.entity_id');
      $query->join('message__field_message_private_subject', 'mfmps', 'mfmps.entity_id = mfmptu.entity_id');
      $query->join('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfmptu.entity_id');
      $query->join('message_field_data', 'mfd', 'mfd.mid = mfmptu.entity_id');
      $query->condition('mfd.uid', $currentUid);
      $query->orderBy('mfd.created', 'DESC');
      $query->range(0, 100);
      $receivedMessages = $query->execute()->fetchAllAssoc('mid');
      $resultReceived = [];
      foreach ($receivedMessages as $message) {
        $resultReceived[$message->subject][$message->author][] = $message;
      }
      // get sent messages with same subject
      $result = $resultReceived;
      foreach ($resultReceived as $subject => $resReceived) {
        foreach ($resReceived as $author => $message) {
          $query = \Drupal::database()->select('message__field_message_private_to_user', 'mfmptu');
          $query->fields('mmr', [
            'is_new',
            'mid'
          ]);
          $query->addField('mfmps', 'field_message_private_subject_value', 'subject');
          $query->addField('mfd', 'created', 'created');
          $query->addField('mfd', 'uid', 'author');
          $query->addField('mfmptu', 'field_message_private_to_user_target_id', 'recipient');
          $query->addField('mfmpb', 'field_message_private_body_value', 'body');
          $query->join('mc_message_read', 'mmr', 'mmr.mid = mfmptu.entity_id');
          $query->join('message__field_message_private_subject', 'mfmps', 'mfmps.entity_id = mfmptu.entity_id');
          $query->join('message_field_data', 'mfd', 'mfd.mid = mfmptu.entity_id');
          $query->join('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfmptu.entity_id');
          $query->condition('mfmptu.field_message_private_to_user_target_id', $author);
          $query->condition('mfd.uid', $currentUid);
          $query->condition('mfmps.field_message_private_subject_value', $subject);
          $query->orderBy('mfd.created', 'DESC');
          $query->range(0, 100);
          $sentMessages = $query->execute()->fetchAllAssoc('mid');
          $result[$subject][$author] = $result[$subject][$author] + $sentMessages;
        }
      }
      // format result
      $messagesList = [];
      foreach ($result as $subject => $messageAuthor) {
        $authorUid = array_keys($messageAuthor);
        $keyGroup = $subject . '-' . $currentUid . '-' . array_shift($authorUid);
        $keyGroup = Html::cleanCssIdentifier($keyGroup);
        $messagesList[$subject][$author] = $this->formatUserMessages($messageAuthor);
        $messagesList[$subject][$author]['accordian_class'] = $keyGroup;
        $messagesList[$subject][$author]['messages'] = $this->formatToUserMessages($messageAuthor);
      }
      return [
        '#theme' => 'mtc_core_sent_private_messages',
        '#messages' => $messagesList
      ];
    }

  /**
     * Function that displays received messages
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function receivedPrivateMessages($user)
    {
        // @todo add pager
        // remove cache
        \Drupal::service('page_cache_kill_switch')->trigger();
        $currentUid = \Drupal::currentUser()->id();
        // get received messages
        $query = \Drupal::database()->select('message__field_message_private_to_user', 'mfmptu');
        $query->fields('mmr', [
            'is_new',
            'mid'
        ]);
        $query->addField('mfmps', 'field_message_private_subject_value', 'subject');
        $query->addField('mfd', 'created', 'created');
        $query->addField('mfd', 'uid', 'author');
        $query->addField('mfmpb', 'field_message_private_body_value', 'body');
        $query->addField('mfmptu', 'field_message_private_to_user_target_id', 'recipient');
        $query->join('mc_message_read', 'mmr', 'mmr.mid = mfmptu.entity_id');
        $query->join('message__field_message_private_subject', 'mfmps', 'mfmps.entity_id = mfmptu.entity_id');
        $query->join('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfmptu.entity_id');
        $query->join('message_field_data', 'mfd', 'mfd.mid = mfmptu.entity_id');
        $query->condition('mfmptu.field_message_private_to_user_target_id', $currentUid);
        $query->orderBy('mfd.created', 'DESC');
        $query->range(0, 100);
        $receivedMessages = $query->execute()->fetchAllAssoc('mid');
        $resultReceived = [];
        foreach ($receivedMessages as $message) {
            $resultReceived[$message->subject][$message->author][] = $message;
        }
        // get sent messages with same subject
        $result = $resultReceived;
        foreach ($resultReceived as $subject => $resReceived) {
            foreach ($resReceived as $author => $message) {
                $query = \Drupal::database()->select('message__field_message_private_to_user', 'mfmptu');
                $query->fields('mmr', [
                    'is_new',
                    'mid'
                ]);
                $query->addField('mfmps', 'field_message_private_subject_value', 'subject');
                $query->addField('mfd', 'created', 'created');
                $query->addField('mfd', 'uid', 'author');
                $query->addField('mfmptu', 'field_message_private_to_user_target_id', 'recipient');
                $query->addField('mfmpb', 'field_message_private_body_value', 'body');
                $query->join('mc_message_read', 'mmr', 'mmr.mid = mfmptu.entity_id');
                $query->join('message__field_message_private_subject', 'mfmps', 'mfmps.entity_id = mfmptu.entity_id');
                $query->join('message_field_data', 'mfd', 'mfd.mid = mfmptu.entity_id');
                $query->join('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfmptu.entity_id');
                $query->condition('mfmptu.field_message_private_to_user_target_id', $author);
                $query->condition('mfd.uid', $currentUid);
                $query->condition('mfmps.field_message_private_subject_value', $subject);
                $query->orderBy('mfd.created', 'DESC');
                $query->range(0, 100);
                $sentMessages = $query->execute()->fetchAllAssoc('mid');
                $result[$subject][$author] = $result[$subject][$author] + $sentMessages;
            }
        }
        // format result
        $messagesList = [];
        foreach ($result as $subject => $messageAuthor) {
            $authorUid = array_keys($messageAuthor);
            $keyGroup = $subject . '-' . $currentUid . '-' . array_shift($authorUid);
            $keyGroup = Html::cleanCssIdentifier($keyGroup);
            $messagesList[$subject][$author] = $this->formatUserMessages($messageAuthor);
            $messagesList[$subject][$author]['accordian_class'] = $keyGroup;
        }
        // get message count
        $query = \Drupal::database()->select('mc_message_read', 'mcmr');
        $query->fields('mcmr', [
            'mid'
        ]);
        $query->join('message__field_message_private_to_user', 'mfmptu', 'mfmptu.entity_id = mcmr.mid');
        $query->condition('mfmptu.field_message_private_to_user_target_id', $currentUid);
        $query->condition('mcmr.is_new', 1);
        return [
            '#theme' => 'mtc_core_private_messages',
            '#messages' => $messagesList
        ];
    }

    /**
     * Function that adds sort message list information
     *
     * @param array $userMessages
     * @return array
     */
    function formatUserMessages($userMessages)
    {
        $messages = array_shift($userMessages);
        $tmp = [];
        $newCntMessages = 0;
        foreach ($messages as $message) {
            if ($message->is_new) {
                $newCntMessages ++;
            }
            $tmp[$message->mid] = (array) $message;
        }
        // get author
        $author = [];
        if (count($tmp) > 0) {
            $msgKey = key($tmp);
            $authorUid = $tmp[$msgKey]['author'];
            $author = \Drupal\user\Entity\User::load($authorUid);
            // get user info
            if ($author) {
                $pictoEntity = $author->get('user_picture')->getValue();
                $author = [
                    'pseudo' => $author->getDisplayName(),
                    'uid' => $authorUid
                ];
            }
        }
        // sort by time
        uasort($tmp, function ($a, $b) {
            return strcmp($a["created"], $b["created"]);
        });
        $total_count = count($tmp);
        $new_messages_count = $newCntMessages;
        $messages = $tmp;
        return compact('messages', 'total_count', 'author', 'new_messages_count');
    }

  /**
   * Function that create recipient params.
   * @see sentPrivateMessages()
   *
   * @return array
   */
  function formatToUserMessages($userMessages)  {
    $result = [];
    foreach ($userMessages as $id => $values) {
      foreach ($values as $value) {
        $recepient = User::load($value->recipient);
        if ($recepient) {
          $pictoEntity = $recepient->get('user_picture')->getValue();
          $recepient = [
            'pseudo' => $recepient->getDisplayName(),
            'uid' => $recepient->id(),
          ];
        }
        $value->recipient = $recepient;
        $result[$value->mid] = $value;
      }
    }
    return $result;
  }

}
