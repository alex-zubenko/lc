<?php

namespace Drupal\mtc_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class ForumSubscriber extends RouteSubscriberBase {

  protected function alterRoutes(RouteCollection $collection){
    $routes = $collection->all();
    $route = $routes['forum.page'];
    $route->setDefault('_controller', '\Drupal\mtc_core\Controller\ForumLastComments::forumPage');
  }

}
