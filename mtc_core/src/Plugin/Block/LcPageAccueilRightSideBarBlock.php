<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'LcPageAccueilRightSideBarBlock' block.
 *
 * @Block(
 * id = "lc_page_accueil_right_side_bar_block",
 * admin_label = @Translation("mtc_core page accueil client right sidebar block"),
 * )
 */
class LcPageAccueilRightSideBarBlock extends BlockBase
{

    /* @var \Drupal\Core\Database\Connection */
    protected $db = null;

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build = [];
        // @todo, get rules of display
        $currentRoute = \Drupal::request()->attributes->get('_route');
        if (! in_array($currentRoute, [
            'mtc_core.subscriber.home.program'
        ])) {
            return [];
        }
        $this->db = \Drupal::database();
        $onlineFriends = $this->getOnlineFriends();
        $activeForum = $this->getActiveForums();
        $groupNews = $this->getGroupNews();
        $nextChat = $this->getNextChat();
        $msgInfo = $this->getNewMsgInfo();
        return compact('msgInfo', 'onlineFriends', 'activeForum', 'groupNews', 'nextChat');
    }

    /**
     * Function that obtains new message cnt
     */
    public function getNewMsgInfo()
    {
        $lcConfig = \Drupal::service('mtc_core.config')->get('site');
        $coachUid = $lcConfig['uid_coach'];
        $currentUid = \Drupal::currentUser()->id();
        $query = $this->db->select('mc_message_read', 'mcmr');
        $query->fields('mcmr', [
            'mid'
        ]);
        $query->join('message__field_message_private_to_user', 'mfmptu', 'mfmptu.entity_id = mcmr.mid');
        $query->condition('mfmptu.field_message_private_to_user_target_id', $currentUid);
        $query->condition('mcmr.is_new', 1);
        $newMesCnt = $query->countQuery()
            ->execute()
            ->fetchField();
        return compact('coachUid', 'newMesCnt', 'currentUid');
    }

    /**
     * Function that obtains the next chat
     */
    public function getNextChat()
    {
        $config = \Drupal::configFactory()->getEditable('mtc_core.chatnasteo.settings');
        $title = $config->get('chatnasteo.title');
        $body = $config->get('chatnasteo.body');
        $body = isset($body) ? $body['value'] : '';
        $photoUri = '';
        $photoFid = $config->get('chatnasteo.photo_expert');
        if ($photoFid && isset($photoFid[0])) {
            $file = \Drupal\file\Entity\File::load($photoFid[0]);
            $photoUri = isset($file) ? $file->getFileUri() : '';
        }
       return compact('title', 'body', 'photoUri');
    }

    /**
     * Function that return online users
     *
     * @return \Drupal\Core\Database\An
     */
    public function getOnlineFriends()
    {
        $currentUser = \Drupal::currentUser();
        $query = $this->db->select('flagging', 'f');
        $query->fields('ufd', [
            'uid',
            'name'
        ]);
        $query->addField('fm', 'uri', 'image_uri');
        $query->join('users_field_data', 'ufd', 'ufd.uid = f.entity_id');
        $query->join('user__user_picture', 'uup', 'uup.entity_id = f.uid');
        $query->join('file_managed', 'fm', 'fm.fid = uup.user_picture_target_id');

        $query->condition('f.uid', $currentUser->id());
        $query->condition('ufd.status', 1);
        $query->condition('ufd.access', REQUEST_TIME - 900, '>');
        return $query->execute()->fetchAllAssoc('uid');
    }

    public function getGroupNews()
    {
        $result = [];
        // obtain chat groups
        $query = $this->db->select('node_field_data', 'nfd');
        $query->fields('nfd', [
            'nid',
            'title'
        ]);
        $query->condition('nfd.status', 1);
        $query->condition('nfd.type', 'chat_groups');
        $chatGroups = $query->execute()->fetchAllAssoc('nid');
        // obtain latest associated chatroom
        foreach ($chatGroups as $chatGroup) {
            $query = $this->db->select('node_field_data', 'nfd');
            $query->fields('nfd', [
                'nid',
                'title'
            ]);
            $query->join('node__og_audience', 'nogau', 'nogau.entity_id = nfd.nid');
            $query->condition('nfd.status', 1);
            $query->condition('nfd.type', 'chat');
            $query->orderBy('nfd.created', 'DESC');
            $query->range(0, 1);
            $query->condition('nogau.og_audience_target_id', $chatGroup->nid);
            $chat = $query->execute()->fetchObject();
            $result[$chatGroup->nid]['group'] = $chatGroup;
            $result[$chatGroup->nid]['chat'] = $chat;
        }
        return $result;
    }

    /**
     * Function that returns active Forums
     */
    public function getActiveForums()
    {
        $query = $this->db->select('forum_index', 'fi');
        $query->fields('fi', [
            'nid',
            'title'
        ]);
        $query->leftJoin('node_field_data', 'nfd', 'nfd.nid = fi.nid');
        $query->condition('fi.comment_count', 25, '>');
        $query->condition('nfd.status', 1);
        $query->range(0, 3);
        $query->orderby('fi.last_comment_timestamp', 'DESC');
        return $query->execute()->fetchAllAssoc('nid');
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getCacheTags()
    {
        $uid = \Drupal::currentUser()->id();
        return Cache::mergeTags(parent::getCacheTags(), [
            "user:{$uid}"
        ]);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getCacheContexts()
    {
        return Cache::mergeContexts(parent::getCacheContexts());
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getCacheMaxAge()
    {
        return 0;
    }
}
