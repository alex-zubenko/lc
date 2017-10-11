<?php
namespace Drupal\mtc_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase
{

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function alterRoutes(RouteCollection $collection)
    {
        if ($route = $collection->get('user.login')) {
//            $route->setDefault('_form', '\Drupal\mtc_core\Form\LcUserLoginForm');
            $route->setDefaults([
                '_controller' => '\Drupal\mtc_core\Controller\LcUserLoginController::login'
            ]);
        }
        if ($route = $collection->get('entity.taxonomy_term.canonical')) {
            $route->setDefaults([
                '_controller' => '\Drupal\mtc_core\Controller\LcForumController::redirectTaxonomy'
            ]);
        }
        if ($route = $collection->get('entity.lc_user_profile_entity.canonical')) {
            $route->setDefaults([
                '_controller' => '\Drupal\mtc_core\Controller\LcUserAccountController::homeProfile'
            ]);
        }
    }
}
