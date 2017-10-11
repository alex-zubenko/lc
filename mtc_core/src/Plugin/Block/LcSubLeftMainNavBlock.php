<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Cache\Cache;

/**
 * Provides a main navigation menu after level 2 block.
 *
 * @Block(
 * id = "lc_sub_left_main_nav_block",
 * admin_label = @Translation("mtc_core left main navigation menu block"),
 * category = @Translation("MTC Core Custom block")
 * )
 */
class LcSubLeftMainNavBlock extends BlockBase {

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build['items'] = [];
        $menuTree = \Drupal::menuTree();
        $parameters = \Drupal::menuTree()->getCurrentRouteMenuTreeParameters('main');

        $activeTrail = array_values(array_filter($parameters->activeTrail));
        if (empty($activeTrail))
            return $build;
        $countTrail = count($activeTrail);
        $parentIndex = $countTrail - 2 > 0 ? $countTrail - 2 : 0;

        $activeTrail = is_array($activeTrail) ? $activeTrail[$parentIndex] : $activeTrail;
        $attribute = new \Drupal\Core\Template\Attribute();
        // remove level < 2 menus
        if (empty($activeTrail) || $countTrail < 2)
            return $build;
        $menuContentIds = \Drupal::entityQuery('menu_link_content')->condition('parent', $activeTrail)
            ->condition('enabled', 1)
            ->sort('weight', 'ASC')
            ->execute();
        // get parent uuid
        $parentUuid = substr($activeTrail, 18);
        $parent = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(array(
            'uuid' => $parentUuid
        ));
        $parent = is_array($parent) ? array_shift($parent) : $parent;
        $menuContentList = MenuLinkContent::loadMultiple($menuContentIds);

        // set parent
        foreach ($menuContentList as $menuContent) {
            $build['items'][] = $this->buildContent($menuContent);
        }
        // set parent
        $build['parent'] = $this->buildContent($parent);
        return $build;
    }

    /*
     * Function that builds the menu link
     * @menuContent Drupal\menu_link_content\Entity\MenuLinkContent
     * @return array
     */
    public function buildContent($menuContent)
    {

      $currentRoute = \Drupal::service('path.current')->getPath();
        if (empty($menuContent))
            return null;
        $attribute = new \Drupal\Core\Template\Attribute();
        $urlObject = $menuContent->getUrlObject();
        $g = $urlObject->isRouted();
        if ($urlObject->isRouted()) {
          /** @var \Drupal\Core\Url $url */
            $url = Url::fromRoute($urlObject->getRouteName(), $urlObject->getRouteParameters(), $urlObject->getOptions());
            $ff = $url->getInternalPath();
          if($currentRoute == '/'.$ff ) {
            $attribute->addClass('active');
          }
        } else {
            $url = $urlObject->getUri();
        }
        return [
            'title' => $menuContent->get('title')->value,
            'url' => $url,
            'attributes' => $attribute,
            'isParent' => false
        ];
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


