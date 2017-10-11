<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Class LcTherapyController.
 *
 * @package Drupal\mtc_core\Controller
 */
use Drupal\message\Entity\Message;

class LcTherapyController extends ControllerBase
{

    /** @var int  max default pager item limit */
    protected $pageLimit = 20;

    /** @var int default offset page */
    protected $pageOffset = 0;

    /** @var int $uidCoach */
    protected $uidCoach = null;

    // @todo in config file
    protected $maxWordCharacter = 150;

    /** */
    public function getIdCoach()
    {
        if (is_null($this->uidCoach)) {
            $lcConfig = \Drupal::service('mtc_core.config')->get('site');
            $this->uidCoach = $lcConfig['uid_coach'] ?? 68;
        }
        return $this->uidCoach;
    }

    /**
     * Method that retrieves node content use serveur therapeutic
     *
     * @param String $nodeType
     *            type de node (hor article-mag,ensemble de type)
     * @param number $offset
     * @param number $limit
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getNodeContentList($nodeType, $offset = 0, $limit = 2)
    {
        // check params
        $limit = ($limit > $this->pageLimit || $limit < 1 || empty($limit)) ? $this->pageLimit : $limit;
        $offset = ($offset < 0 || empty($offset)) ? $this->pageOffset : $offset;
        $contentType = null;
        $result = [];
        $query = \Drupal::entityQuery('node');
        switch ($nodeType) {
            case 'chat_general':
                $contentType = 'chat_nasteo';
                break;
            case 'chat_groups':
                $query->condition('type', 'chat_groups');
                $contentType = 'chat_groups';
                break;
            case 'article-mag':
                $query->condition('type', [
                    'blog_expert',
                    'dossier',
                    'dossier_recette',
                    'diaporama'
                ], 'IN');
                $contentType = 'node';
                break;
            case 'comment_forum':
                $query = \Drupal::entityQuery('comment');
                $query->condition('comment_type', 'comment_forum');
                $contentType = 'comment_forum';
                break;

            default:
                $contentType = null;
            // exception
        }
        switch ($contentType) {
            case 'comment_forum':
                $query->condition('status', 1);
                $query->range($offset, $limit);
                $query->sort('changed', 'DESC');
                $qRes = $query->execute();
                $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadMultiple($qRes);
                // Create object
                foreach ($comments as $comment) {
                    $entityNid = $comment->get('entity_id')
                        ->first()
                        ->get('target_id')
                        ->getValue();
                    // get user picture
                    $queryPicto = \Drupal::database()->select('user__user_picture', 'uup');
                    $queryPicto->fields('fm', [
                        'uri'
                    ]);
                    $queryPicto->leftjoin('file_managed', 'fm', 'fm.fid = uup.user_picture_target_id');
                    $queryPicto->condition('uup.entity_id', $comment->getOwnerId(), '=');
                    $pictoUri = $queryPicto->range(0, 1)
                        ->execute()
                        ->fetch();
                    $pictoUri = empty($pictoUri) ? 'public://avatar_selection/anonyme.jpg' : $pictoUri->uri;
                    $result[] = [
                        'subject' => $comment->getCommentedEntity()->getTitle(),
                        'changed' => $comment->getChangedTime(),
                        'body' => Unicode::truncate(strip_tags($comment->get('comment_body')->value), $this->maxWordCharacter, true, true),
                        'cid' => $comment->get('cid')->value,
                        'owner_name' => $comment->getOwner()->getDisplayName(),
                        'owner_avatar' => ImageStyle::load('large')->buildUrl($pictoUri),
                        'owner_url' => Url::fromRoute('entity.lc_user_profile_entity.canonical', [
                            'lc_user_profile_entity' => $comment->getOwnerId()
                        ])->toString(),
                        'forum_subject_nid' => $entityNid,
                        'forum_subject_url' => Url::fromRoute('entity.node.canonical', [
                            'node' => $entityNid
                        ])->toString()
                    ];
                }
                break;
            case 'chat_groups':
                $query->condition('status', 1);
                $query->range($offset, $limit);
                $query->sort('created', 'DESC');
                $chatGroupNids = $query->execute();
                // Create object
                foreach ($chatGroupNids as $chatGroupNid) {
                    $queryChat = \Drupal::database()->select('node_field_data', 'nfd');
                    $queryChat->fields('nfd', [
                        'nid'
                    ]);
                    $queryChat->leftjoin('node__og_audience', 'noga', 'noga.entity_id = nfd.nid');
                    $queryChat->leftjoin('node__field_date_chat', 'nfdc', 'nfdc.entity_id = nfd.nid');
                    $queryChat->condition('nfd.type', 'chat', '=');
                    $queryChat->condition('noga.og_audience_target_id', $chatGroupNid, '=');
                    $queryChat->orderBy('nfdc.field_date_chat_value', 'DESC');
                    $chatNid = $queryChat->range(0, 1)
                        ->execute()
                        ->fetch();
                    if (empty($chatNid))
                        continue;
                    $chat = \Drupal\node\Entity\Node::load($chatNid->nid);
                    $chatGroup = \Drupal\node\Entity\Node::load($chatGroupNid);
                    $start_date = strtotime($chat->get('field_date_chat')->value);
                    $data = [
                        'chat_title' => $chat->getTitle(),
                        'chat_changed' => $chat->getChangedTime(),
                        'chat_body' => Unicode::truncate(strip_tags($chat->body->value), $this->maxWordCharacter, true, true),
                        'chat_start_date' => strtotime($chat->get('field_date_chat')->value),
                        'chat_start_date_unformat' => $chat->get('field_date_chat')->value,
                        'chat_link' => Url::fromRoute('entity.node.canonical', [
                            'node' => $chat->id()
                        ])->toString(),
                        'chat_group_title' => $chatGroup->getTitle(),
                        'chat_group_body' => Unicode::truncate(strip_tags($chatGroup->body->value), $this->maxWordCharacter, true, true),
                        'chat_group_link' => Url::fromRoute('entity.node.canonical', [
                            'node' => $chatGroup->id()
                        ])->toString(),
                        'chat_image_url' => null
                    ];
                    // get chat image
                    if (null !== $chat->get('field_photo') && null !== $chat->get('field_photo')->first()) {
                        $photoFid = $chat->get('field_photo')
                            ->first()
                            ->get('target_id')
                            ->getValue();
                        $file = File::load($photoFid);
                        $data['chat_image_url'] = ImageStyle::load('large')->buildUrl($file->getFileUri());
                    }
                    $result[] = $data;
                }
                usort($result, function ($item1, $item2) {
                    return $item1['chat_start_date'] < $item2['chat_start_date'];
                });
                break;

            case 'node':

                $query->condition('status', 1);
                $query->range($offset, $limit);
                $query->sort('created', 'DESC');
                $qRes = $query->execute();
                $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($qRes);
                // Create object
                foreach ($nodes as $node) {
                    $data = [
                        'title' => $node->getTitle(),
                        'changed' => $node->getChangedTime(),
                        'body' => Unicode::truncate(strip_tags($node->body->value), $this->maxWordCharacter, true, true),
                        'nid' => $node->id(),
                        'link' => Url::fromRoute('entity.node.canonical', [
                            'node' => $node->id()
                        ])->toString()
                    ];
                    if (null !== $node->field_date_chat) {
                        $data['start_date'] = strtotime($node->get('field_date_chat')->value);
                    }
                    if (null !== $node->field_photo && null !== $node->get('field_photo')->first()) {
                        $photoFid = $node->get('field_photo')
                            ->first()
                            ->get('target_id')
                            ->getValue();
                        $file = File::load($photoFid);
                        $data['image_url'] = ImageStyle::load('large')->buildUrl($file->getFileUri());
                    }
                    if (null !== $node->field_rating && null !== $node->get('field_rating')->first()) {
                        $data['rating'] = $node->get('field_rating')
                            ->first()
                            ->get('rating')
                            ->getValue();
                    }
                    if (null !== $node->field_tag_transverse_format && null !== $node->get('field_tag_transverse_format')->first()) {
                        $termFormatTid = $node->get('field_tag_transverse_format')
                            ->first()
                            ->get('target_id')
                            ->getValue();
                        $data['term_format'] = Term::load($termFormatTid)->getName();
                    }
                    // get comment count
                    $cmntCount = \Drupal::entityQuery('comment')->condition('entity_id', $node->id())
                        ->condition('status', 1)
                        ->count()
                        ->execute();
                    $data['comment_count'] = $cmntCount;
                    $result[] = $data;
                }
                break;
            case 'chat_nasteo':
                $config = \Drupal::configFactory()->getEditable('mtc_core.chatnasteo.settings');
                $data['title'] = $config->get('chatnasteo.title');
                $data['body'] = $config->get('chatnasteo.body');
                $data['start_date'] = time();
                $data['body'] = isset($data['body']) ? Unicode::truncate(strip_tags($data['body']['value']), $this->maxWordCharacter, true, true) : '';
                $data['image_url'] = '';
                $data['link'] = '/' . Url::fromRoute('mtc_core.chat_nasteo')->getInternalPath();
                $photoFid = $config->get('chatnasteo.photo_expert');
                if ($photoFid && isset($photoFid[0])) {
                    $file = \Drupal\file\Entity\File::load($photoFid[0]);
                    $data['image_url'] = isset($file) ? file_create_url($file->getFileUri()) : '';
                }
                $data['changed'] = time();
                $data['comment_count'] = 0;
                $result[] = $data;
                break;
            default:
                break;
        }
        return new JsonResponse($result);
    }

    /**
     * Retrieve messages from coach
     *
     * @param number $offset
     * @param number $limit
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function retrieveMessageCoach($offset, $limit)
    {
        $db = \Drupal::database();
        $uid = \Drupal::currentUser()->id();

        //Old functionality
//        $limit = ($limit > $this->pageLimit || $limit < 1 || empty($limit)) ? $this->pageLimit : $limit;

        $limit = ($limit < 1 || empty($limit)) ? NULL : $limit;
        $offset = ($offset < 0 || empty($offset)) ? $this->pageOffset : $offset;
        $offset = ($offset < 0 || empty($offset)) ? $this->pageOffset : $offset;

        $queryToCoach = $db->select('message_field_data', 'mfd');
        $queryToCoach->addField('mfmpb', 'field_message_private_body_value');
        $queryToCoach->addField('mfd', 'created');
        $queryToCoach->addField('mfd', 'uid');
        $queryToCoach->leftJoin('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfd.mid');
        $queryToCoach->leftJoin('message__field_message_private_to_user', 'mfmpu', 'mfmpu.entity_id = mfd.mid');
        $queryToCoach->condition('mfd.uid', $uid, "=");
        $queryToCoach->condition('mfmpu.field_message_private_to_user_target_id', $this->getIdCoach(), '=');
        //Old functionality
//        $queryToCoach->range($offset, $limit);

        $queryToUser = \Drupal::database()->select('message_field_data', 'mfd');
        $queryToUser->addField('mfmpb', 'field_message_private_body_value');
        $queryToUser->addField('mfd', 'created');
        $queryToUser->addField('mfd', 'uid');
        $queryToUser->leftJoin('message__field_message_private_body', 'mfmpb', 'mfmpb.entity_id = mfd.mid');
        $queryToUser->leftJoin('message__field_message_private_to_user', 'mfmpu', 'mfmpu.entity_id = mfd.mid');
        $queryToUser->condition('mfd.uid', $this->getIdCoach(), "=");
        $queryToUser->condition('mfmpu.field_message_private_to_user_target_id', $uid, '=');

        $res = \Drupal::database()->select($queryToCoach->union($queryToUser))
          ->fields(NULL, array(
            'field_message_private_body_value',
            'created',
            'uid'
          ));

        if (empty($limit)) {
            $count = $res->countQuery()->execute()->fetchField();
            $res->range($offset, ($count - $offset));
        }
        else {
            $res->range($offset, $limit);
        }

        $res->orderBy('created', 'ASC');
        $res = $res->execute()->fetchAll();
        $user_data = [];
        $result = [];
        foreach ($res as $data) {
            $name = $photo = '';
            if (isset($data->uid) && ! empty($data->uid)) {
                if (isset($user_data[$data->uid])) {
                    $name = $user_data[$data->uid]['name'];
                    $photo = $user_data[$data->uid]['photo'];
                } else {
                    /** @var \Drupal\user\Entity\User $user */
                    $user = User::load($data->uid);
                    $name = $user->getAccountName();
                    /** @var \Drupal\file\Entity\File $file */
                    $file = $user->user_picture->entity;
                    if (! empty($file)) {
                        $photo = $file->url();
                    }
                    $user_data[$data->uid] = [
                        'name' => $name,
                        'photo' => $photo
                    ];
                }
            }
            $result[] = [
                'message' => $data->field_message_private_body_value,
                'time' => date('m/d/y h:i', $data->created),
                'type' => $data->uid == $this->getIdCoach() ? 'coach' : 'user',
                'name' => $name,
                'photo' => $photo
            ];
        }
        return new JsonResponse($result);
    }

    /**
     * Post messages to coach
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function postMessageCoach()
    {
        // check params
        $content = \Drupal::request()->getContent();
        $currentUser = \Drupal::currentUser();

        $params = json_decode($content, TRUE);
        if (empty($params)) {
            return new JsonResponse([
                'Empty content'
            ]);
        }
        if (empty($params['body'])) {
            return new JsonResponse([
                'Body is empty'
            ]);
        }
        if ($currentUser->isAnonymous()) {
            return new JsonResponse(null, 404);
        }
        $message = Message::create([
            'template' => 'private_message'
        ]);
        $params['subject'] = $params['subject'] ?? '';
        $message->set('field_message_private_to_user', $this->getIdCoach());
        $message->set('uid', $currentUser->id());
        $message->set('field_message_private_subject', $params['subject']);
        $message->set('field_message_private_body', $params['body']);
        $message->save();

        return new JsonResponse([
            true
        ]);
    }
}
