<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Provides a Sub Main Menu of active elements for navigation
 *
 * @Block(
 * id = "lc_sub_main_nav_block",
 * admin_label = @Translation("mtc_core sub main menu navigation block"),
 * category = @Translation("mtc_core Custom block")
 * )
 */
class LcSubMainNavBlock extends BlockBase {

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build['items'] = [];
        $taxonomyTerm = \Drupal::routeMatch()->getParameter('taxonomy_term');
        // exclude taxonmoy terms && homepage
        if ($taxonomyTerm || \Drupal::service('path.matcher')->isFrontPage()) {
            return $build;
        }

        $menu_name = 'main';
        if (!\Drupal::currentUser()->isAnonymous()) {
          $menu_name = 'main-navigation';
        }

        $blog = \Drupal::routeMatch()->getRouteName() == 'view.blog.blog_all' || \Drupal::routeMatch()->getRouteName() == 'view.blog.blog_user_all';
        $uuid = NULL;
        if ($blog) {
          $menu_id = \Drupal::entityQuery('menu_link_content')
            ->condition('title', 'Communauté')
            ->condition('menu_name', $menu_name)
            ->execute();
          $menu_id = end(array_keys($menu_id));
          $uuid = 'menu_link_content:' . MenuLinkContent::load($menu_id)->uuid();
        }

        $currentPath = \Drupal::service('path.current')->getPath();
        $menu_tree = \Drupal::menuTree();
        $parameters = \Drupal::menuTree()->getCurrentRouteMenuTreeParameters($menu_name);
        $activeTrail = array_filter($parameters->activeTrail);
        $activeTrail = end($activeTrail);
        if (!$blog) {
          $uuid = $activeTrail;
        }
        $menuContentIds = \Drupal::entityQuery('menu_link_content')->condition('parent', $uuid)
            ->condition('enabled', 1)
            ->sort('weight', 'ASC')
            ->execute();
        $menuContentList = MenuLinkContent::loadMultiple($menuContentIds);
        foreach ($menuContentList as $menuContent) {
            $urlObject = $menuContent->getUrlObject();
            $inActiveTrail = false;
            $internalpath = '';
            $attribute = new \Drupal\Core\Template\Attribute();
            if (\Drupal::routeMatch()->getRouteName() == 'view.blog.blog_user_all' && $menuContent->get('link')->getValue()[0]['uri'] == 'internal:/myblog') {
              $attribute->addClass('active');
            }
            if ($urlObject->isRouted()) {
                $url = Url::fromRoute($urlObject->getRouteName(), $urlObject->getRouteParameters(), $urlObject->getOptions());
                $internalpath = $url->getInternalPath();
                $inActiveTrail = '/' . $internalpath == $currentPath ? true : false;
            } else {
                $url = $urlObject->getUri();
            }

            $build['items'][] = [
                'title' => $menuContent->get('title')->value,
                'url' => $url,
                'in_active_trail' => $inActiveTrail,
                'attributes' => $attribute
            ];
        }
        return $build;
    }

    public function getCacheTags()
    {
        // With this when your node change your block will rebuild
        if ($node = \Drupal::routeMatch()->getParameter('node')) {
            // if there is node add its cachetag
            return Cache::mergeTags(parent::getCacheTags(), array(
                'node:' . $node->id()
            ));
        } else {
            // Return default tags instead.
            return parent::getCacheTags();
        }
    }

    public function getCacheContexts()
    {
        // if you depends on \Drupal::routeMatch()
        // you must set context of this block with 'route' context tag.
        // Every new route this block will rebuild
        return Cache::mergeContexts(parent::getCacheContexts(), array(
            'route'
        ));
    }
}
