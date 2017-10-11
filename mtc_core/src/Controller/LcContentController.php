<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\block\BlockViewBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Link;

/**
 * Class LcContentController.
 *
 * @package Drupal\mtc_core\Controller
 */
class LcContentController extends ControllerBase
{

    /**
     * Obtains header,footer and sidebar of the site.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function themeRegions()
    {
        // remove cache
        \Drupal::service('page_cache_kill_switch')->trigger();
        $blockRepo = \Drupal::service('block.repository')->getVisibleBlocksPerRegion();
        $renderer = \Drupal::service('renderer');

        $headerMenu = [
            'header',
            'search',
            'user_menu',
            'primary_menu'
        ];
        $footerMenu = [
            'footer_menu'
        ];
        $footerMenuExclude = [
            'views_block__frontpage_blocks_block_6'
        ];
        $sideBarMenu = [
            'sidebar_second'
        ];
        $content = [];
        $content['header'] = '';
        $content['footer'] = '';
        $content['sideBar'] = '';

        // construct header elements
        foreach ($headerMenu as $headerElement) {
            foreach ($blockRepo[$headerElement] as $element) {
                if (empty($element))
                    continue;
                $blockContent = BlockViewBuilder::lazyBuilder($element->id(), 'full');
                $content['header'] .= $renderer->render($blockContent);
            }
        }
        // construct footer elements
        foreach ($footerMenu as $footerElement) {
            foreach ($blockRepo[$footerElement] as $key => $element) {
                if (empty($element) || in_array($key, $footerMenuExclude))
                    continue;
                $blockContent = BlockViewBuilder::lazyBuilder($element->id(), 'full');
                $content['footer'] .= $renderer->render($blockContent);
            }
        }
        // construct sidebar elements
        foreach ($sideBarMenu as $sideBarElement) {
            foreach ($blockRepo[$sideBarElement] as $element) {
                $blockContent = BlockViewBuilder::lazyBuilder($element->id(), 'full');
                if ($element->id() == 'temoignage') {
                    $lcConfig = \Drupal::service('mtc_core.config')->get('site');
                    $bid = $lcConfig['block_right_sidebar_bid_testimony'] ?? null;
                    $block = isset($bid) ? \Drupal\block_content\Entity\BlockContent::load($bid) : null;
                    $blockContent = isset($block) ? $this->displayRightSideBlock($block) : '';
                    $content['sideBar'] .= isset($blockContent) ? $renderer->render($blockContent) : '';
                } else {
                    $content['sideBar'] .= $renderer->render($blockContent);
                }
            }
        }
        // @todo make it parametrable
        $duration = 6;
        $premium = 1;
        $content['offer'] = \Drupal::service('mtc_core.ws_client')->getRecommendedOffer($duration, $premium);
        return new JsonResponse([
            'content' => $content
        ]);
    }

    /*
     * Function that return the session name of the site
     * @todo ,remove once new prog is online
     */
    public function sesssionName()
    {
        $name = session_name();
        return new JsonResponse([
            'content' => $name
        ]);
    }

    /**
     * Function that obtains offer from bo payment
     *
     * @param number $duration
     * @param number $isPremium
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getRecommendedOffer($duration, $premium)
    {
        $content = \Drupal::service('mtc_core.ws_client')->getRecommendedOffer($duration, $premium);
        return new JsonResponse([
            'content' => $content
        ]);
    }

    /**
     * hello
     *
     * @param string $name
     * @return string
     */
    public function displayRightSideBlock($blockContent)
    {
        if (! isset($blockContent)) {
            return '';
        }
        // get image
        $title = $blockContent->hasField('info') ? $blockContent->get('info')->value : null;
        $body = $blockContent->hasField('body') ? $blockContent->get('body')->value : null;
        $link = $blockContent->hasField('field_lien') ? $blockContent->get('field_lien')->getValue() : null;
        $image = $blockContent->hasField('field_image') ? $blockContent->get('field_image')->getValue() : null;
        // create image
        if ($image) {
            $fid = $image[0]['target_id'];
            $field_image['height'] = $image[0]['height'];
            $field_image['width'] = $image[0]['width'];
            $field_image['alt'] = $image[0]['title'];
            $file = \Drupal\file\Entity\File::load($fid);
            $field_image['image_path'] = $file != null ? $file->getFileUri() : null;
        }
        if ($link) {
            $uri = $link[0]['uri'];
            $field_link['title'] = $link[0]['title'];
            $url = Url::fromUri($uri);
            $field_link['uri'] = $url->toString();
        }
        return [
            '#theme' => 'mtc_core_right_sidebar_block',
            '#content' => compact('title', 'body', 'field_image', 'field_link')
        ];
    }

    public function homepageVideoPopup() {
        $build = array(
          '#type' => 'html_tag',
          '#tag' => 'iframe',
          '#attached' => [
            'library' => ['linecoaching_theme/player-vzaar'],
          ],
          '#attributes' => [
            'id' => 'vzvd-11140242',
            'width' => '100%',
            'src' => '//view.vzaar.com/11140242/player?apiOn=true',
          ],
        );
        return $build;
    }
}
