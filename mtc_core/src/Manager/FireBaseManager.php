<?php

/**
 * @file
 * Contains \Drupal\mtc_core\FireBaseManager.
 */
namespace Drupal\mtc_core\Manager;

use Kreait\Firebase;

/**
 * Class FireBaseManager.
 *
 * @package Drupal\mtc_core
 */
class FireBaseManager
{

    const SERVICE_ACCOUNT = DRUPAL_ROOT . '/sites/default/google-service-account.json';

    protected $firebase;

    /**
     * Content entity that allowed for push notifications from drupal
     *
     * @var array
     */
    protected $validPushContentType = [
        'message',
        'comment',
        'flagging'
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Function that obtains firebase object
     */
    public function getFirebase()
    { // lazy loading
        $this->firebase = null;
        // check if file exists
        if (file_get_contents(self::SERVICE_ACCOUNT)) {
            $this->firebase = (new Firebase\Factory())->withCredentials(self::SERVICE_ACCOUNT)->create();
        }
        if (empty($this->firebase)) {
            throw new \Exception('Le fichier google service account manque pour firebase manque' . self::SERVICE_ACCOUNT);
        }
        return $this->firebase;
    }

    /**
     * push elemens
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *            The entity object.
     * @return params array
     */
    public function pushFireBaseContent(\Drupal\Core\Entity\EntityInterface $entity)
    {
        if (! $entity instanceof \Drupal\Core\Entity\EntityInterface) {
            return;
        }
        $type = $entity->getEntityTypeId();
        if (! in_array($type, $this->validPushContentType)) {
            return;
        }
        switch ($type) {
            case 'message':
                $params = $this->pushMessageType($entity);
                break;
            case 'comment':
                $params = $this->pushCommentType($entity);
                break;
            case 'flagging':
                $params = $this->pushFlagType($entity);
                break;
            default:
                return;
        }
    }

    /**
     * push message element.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *            The entity object.
     * @return params array
     */
    public function pushMessageType(\Drupal\Core\Entity\EntityInterface $entity)
    {
        if ($entity->getEntityTypeId() != 'message') {
            return;
        }
        // $owner = $entity->getOwner();
        // $ownerMail = $owner->getEmail();
        // @todo case of several recepients
        $recipientUid = $entity->get('field_message_private_to_user')->getValue();
        $recipientUid = $recipientUid[0]['target_id'];
        $message = $entity->get('field_message_private_body')->getValue();
        $message = $message[0]['value'];
        $subject = $entity->get('field_message_private_subject')->getValue();
        $subject = $subject[0]['value'];
        $recipient = \Drupal\user\Entity\User::load($recipientUid);
        $mail = isset($recipient) ? $recipient->getEmail() : null;
        $viewed = false;
        $creation_date = REQUEST_TIME;
        $link = \Drupal\Core\Url::fromRoute('message_private.messages.inbox', [
            'user' => $recipientUid
        ], [
            'absolute' => TRUE
        ]);
        $link = $link->toString();
        $type = 'privatemsg';
        $params = [];
        $params['data'] = compact('type', 'message', 'link', 'mail', 'creation_date', 'viewed');
        $params['uri'] = 'notifications/' . $recipientUid;
        $this->addToFireBase($params);
    }

    /**
     * push comment element.
     *
     * @param \Drupal\comment\Entity\Comment $entity
     *            The entity object.
     */
    public function pushFlagType(\Drupal\flag\Entity\Flagging $entity)
    {
        if ($entity->getEntityTypeId() != 'flagging') {
            return null;
        }
        // check if anonmyous
        $currentUser = \Drupal::currentUser();
        if ($currentUser->isAnonymous()) {
            return;
        }
        // get flag type
        $type = $entity->get('flag_id')->getValue();
        $type = $type[0]['value'];

        $message = '';
        // construct message based on flag types
        if ($type == 'friend_list') {
            $recipientUid = $entity->get('entity_id')->getValue();
            $recipientUid = $recipientUid[0]['value'];
            $message = $currentUser->getDisplayName() . " suis votre posts";
            $recipient = \Drupal\user\Entity\User::load($recipientUid);
            $mail = $recipient->getEmail();

            $link = \Drupal\Core\Url::fromRoute('entity.user.canonical', [
                'user' => $currentUser->id()
            ], [
                'absolute' => TRUE
            ]);
            $link = $link->toString();
        }
        // construct message based on flag types
        if ($type == 'flag_like_forum_comment') {
            $commentCid = $entity->get('entity_id')->getValue();
            $commentCid = $commentCid[0]['value'];
            $comment = \Drupal\comment\Entity\Comment::load($commentCid);
            $recipient = $comment->getOwner();
            $recipientUid = $recipient->id();
            $mail = $recipient->getEmail();
            $message = $currentUser->getDisplayName() . " aime votre commentaire";
            $link = \Drupal\Core\Url::fromRoute('entity.comment.canonical', [
                'comment' => $commentCid
            ], [
                'absolute' => TRUE
            ]);
            $link = $link->toString();
        }
        // construct message based on flag types
        if ($type == 'blog_post_follow') {
            $nid = $entity->get('entity_id')->getValue();
            $nid = $nid[0]['value'];
            $node = \Drupal\node\Entity\Node::load($nid);
            $owner = $node->getOwner();
            $recipientUid = $node->getOwnerId();
            $message = $currentUser->getDisplayName() . " suis votre blog";
            $recipient = \Drupal\user\Entity\User::load($recipientUid);
            $mail = $owner->getEmail();

            $link = \Drupal\Core\Url::fromRoute('entity.node.canonical', [
                'node' => $nid
            ], [
                'absolute' => TRUE
            ]);
            $link = $link->toString();
        }
        $viewed = false;
        $creation_date = REQUEST_TIME;
        $params['data'] = compact('type', 'mail', 'message', 'link', 'creation_date', 'viewed');
        $params['uri'] = 'notifications/' . $recipientUid;
        $this->addToFireBase($params);
    }

    /**
     * push comment element.
     *
     * @param \Drupal\comment\Entity\Comment $entity
     *            The entity object.
     */
    public function pushCommentType(\Drupal\comment\Entity\Comment $entity)
    {
        if ($entity->getEntityTypeId() != 'comment') {
            return null;
        }
        $parentComment = $entity->getParentComment();
        // inform allow reponses of parents
        if (! $parentComment) {
            return null;
        }
        // get comment type
        $commentType = $entity->get('field_name')->getValue();
        $commentType = $commentType[0]['value'];

        $entityId = $entity->get('entity_id')->getValue();
        $entityId = $entityId[0]['target_id'];

        $link = \Drupal\Core\Url::fromRoute('entity.node.canonical', [
            'node' => $entityId
        ], [
            'absolute' => TRUE
        ]);
        $link = $link->toString();

        $recipient = $parentComment->getOwner();
        $recipientUid = $recipient->id();
        $mail = $recipient->getEmail();
        $message = $entity->get('comment_body')->getValue();
        $message = $message[0]['value'];
        $subject = $entity->get('subject')->getValue();
        $subject = $subject[0]['value'];
        $subject = 'new comment-' . $subject;
        $viewed = false;
        $creation_date = REQUEST_TIME;
        $type = $commentType;
        $params['data'] = compact('type', 'message', 'link', 'mail', 'creation_date', 'viewed');
        $params['uri'] = 'notifications/' . $recipientUid;
        $this->addToFireBase($params);
    }

    /**
     * push user online element.
     *
     * @param Drupal\user\Entity\User $account
     *            The entity object.
     * @return params array
     */
    public function pushFireBaseUserLogin(\Drupal\user\Entity\User $account)
    {
        $name = $account->get('name')->value;
        $uid = $account->get('uid')->value;
        $mail = $account->get('mail')->value;
        $params['data'] = compact('name', 'uid', 'mail');
        // add to online list
        $params['uri'] = 'user-names-online/' . $uid;
        $this->updateToFireBase($params);

        // add moderation list
        // list of roles
        $roles = $account->getRoles();
        $params['uri'] = 'moderators/' . $uid;
        $moderator = false;

        if (in_array('chat_moderator', $roles) || in_array('chat_manager', $roles)) {
            $moderator = true;
        }
        $params['data'] = [
            'status' => $moderator
        ];
        $this->updateToFireBase($params);

        // update list of users
        $params['uri'] = 'users/' . $uid;
        $id = $uid;
        $login_date = REQUEST_TIME;
        // set roles
        $roles = $this->chatRoles($roles);
        $params['data'] = compact('name', 'uid', 'login_date', 'mail', 'roles');
        $this->updateToFireBase($params);
    }

    /**
     * Function that defines chat roles
     *
     * @param
     *            Array
     * @return params Array
     */
    public function chatRoles($userRoles)
    {
        $roles = [];
        if (in_array('chat_moderator', $userRoles)) {
            $roles[] = 'chat_moderator';
        }
        if (in_array('utilisateur_abonne', $userRoles)) {
            $roles[] = 'utilisateur_abonne';
        }
        if (in_array('filtered_ancien_abonne', $userRoles)) {
            $roles[] = 'filtered_ancien_abonne';
        }
        if (in_array('filtered_inscrit', $userRoles)) {
            $roles[] = 'filtered_inscrit';
        }
        if (in_array('chat_manager', $userRoles)) {
            $roles[] = 'chat_manager';
        }
        return $roles;
    }

    /**
     * delete user online element.
     *
     * @param \Drupal\Core\Session\AccountProxy $account
     *            The entity object.
     * @return params array
     */
    public function pushFireBaseUserLogout(\Drupal\Core\Session\AccountProxy $account)
    {
        $uid = $account->id();
        $params['data'] = compact('uid');
        // remove from online list
        $params['uri'] = 'user-names-online/' . $uid;
        $this->removeFromFireBase($params);
        // remove from moderation list
        $params['uri'] = 'moderators/' . $uid;
        $this->removeFromFireBase($params);
    }

    /**
     * add content to firebase
     *
     * @param unknown $params
     * @return boolean
     */
    public function updateToFireBase($params)
    {
        if ($params) {
            $database = $this->getFirebase()->getDatabase();
            try {
                $newPost = $database->getReference($params['uri'])->update($params['data']);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                \Drupal::logger('mtc_core')->error($message);
            }
        }
        return true;
    }

    /**
     * add content to firebase
     *
     * @param unknown $params
     * @return boolean
     */
    public function addToFireBase($params)
    {
        if ($params) {
            $database = $this->getFirebase()->getDatabase();
            try {
                $newPost = $database->getReference($params['uri'])->push($params['data']);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                \Drupal::logger('mtc_core')->error($message);
            }
        }
        return true;
    }

    /*
     * remove content from firebase
     */
    public function removeFromFireBase($params)
    {
        if ($params) {
            $database = $this->getFirebase()->getDatabase();
            try {
                $newPost = $database->getReference($params['uri'])->remove();
            } catch (\Exception $e) {
                $message = $e->getMessage();
                \Drupal::logger('mtc_core')->error($message);
            }
        }
        return true;
    }
}
