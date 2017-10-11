<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\comment\Entity\Comment;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LcCommentController.
 *
 * @package Drupal\mtc_core\Controller
 */
class LcCommentController extends ControllerBase{

    // display default number of comments
    const DEFAULT_INIT_COMMENT_NUM = 2;

    const DEFAULT_LIMIT_COMMENT_NUM = 200;

    /**
     * Default loading of comments only 2.
     *
     * Return json array of comments.
     */
    public function index($nid)
    {
        // load comment of node
        $limit = self::DEFAULT_INIT_COMMENT_NUM ?? 2;
        $cids = \Drupal::entityQuery('comment')->condition('entity_id', $nid)
            ->condition('entity_type', 'node')
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->execute();
        $commentView = \Drupal::entityTypeManager()->getViewBuilder('comment');
        $content = '';
        foreach ($cids as $cid) {
            $comment = Comment::load($cid);
            $contentView = $commentView->view($comment, 'full');
            $content .= render($contentView);
        }
        $response = new Response();
        $response->setContent(json_encode(array(
            'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Function that load all comments.
     *
     * Return json array of comments.
     */
    public function loadAll($nid)
    {
        // load all comments of node
        $limit = self::DEFAULT_LIMIT_COMMENT_NUM ?? 100;

        $cids = \Drupal::entityQuery('comment')->condition('entity_id', $nid)
            ->condition('entity_type', 'node')
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->execute();
        $commentView = \Drupal::entityTypeManager()->getViewBuilder('comment');
        $content = '';
        foreach ($cids as $cid) {
            $comment = Comment::load($cid);
            $contentView = $commentView->view($comment, 'full');
            $content .= render($contentView);
        }
        $response = new Response();
        $response->setContent(json_encode(array(
            'content' => $content
        )));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
