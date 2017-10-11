<?php

namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;

class LcUserMyBlogContentController extends ControllerBase {

  public function index() {
   $user_id = \Drupal::currentUser()->id();
    return $this->redirect('view.blog.blog_user_all',['arg_0' => $user_id]);
  }
}