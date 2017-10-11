<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;

/**
 * Displays list of available applications and tools
 *
 * @Block(
 * id = "lc_prog_application_tools_block",
 * admin_label = @Translation("Lc prog:list application tools block"),
 * category = @Translation("MTC Core Custom block")
 * )
 */
class LcProgApplicationToolsBlock extends BlockBase
{

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $currentUser = \Drupal::currentUser();
        if ($currentUser->isAnonymous())
            return [];
        $currentRoute = \Drupal::request()->attributes->get('_route');
        $lcConfig = \Drupal::service('mtc_core.config')->get('site');
        if ($currentUser->id() > $lcConfig['max_uid_old_subscribers']) {
            return [];
        }
        $avatar = file_url_transform_relative(file_create_url('public://avatar_selection/anonyme.jpg'));
        $user = \Drupal\user\Entity\User::load($currentUser->id());
        if ($user->get('user_picture')->entity) {
            $avatar = $user->get('user_picture')->entity->url();
        }
        $svtData = \Drupal::service('mtc_core.svt_manager')->svtProgSidebarClient();
        return compact('svtData', 'avatar');
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
        return Cache::mergeContexts(parent::getCacheContexts(), [
            'route'
        ]);
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
