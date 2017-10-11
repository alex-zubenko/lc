<?php

/**
 * @file
 * Contains \Drupal\mtc_core\Manager\MetaTagManager
 */
namespace Drupal\mtc_core\Manager;

use Drupal\Component\Utility\Unicode;

/**
 * Class MetaTagManager.
 * Handles page meta tag html : links title...
 *
 * @package Drupal\mtc_core
 */
class MetaTagManager
{

    /*
     * @var integer
     * @label('Current page number')
     */
    protected $page = null;

    /*
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request = null;

    /*
     * @var Drupal\Core\Routing\RouteMatchInterface
     */
    protected $route = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->request = \Drupal::request();
        $this->page = $this->request->get('page');
        $this->route = \Drupal::routeMatch();
    }

    /*
     * Function manipulates forum meta
     * @param \Drupal\Core\Entity\EntityInterfac $entity
     * @param array $attachments
     * @return array $attachments
     */
    public function alterForumHeadMeta(\Drupal\Core\Entity\EntityInterface $entity, $attachments)
    {
        if ($entity->getType() != 'forum')
            return $attachments;
        $headerLinks = $attachments['html_head_link'] ?? [];
        foreach ($headerLinks as $index => $link) {
            if ($link[0]['rel'] == "canonical") {
                unset($headerLinks[$index]);
            }
        }
        $attachments['html_head_link'] = $headerLinks;
        return $attachments;
    }

    /*
     * Function that removes unwanted head links
     * @param array $attachments
     * @return array $attachments
     */
    public function alterGeneralHeadLinks($attachments)
    {
        $headerLinks = $attachments['html_head_link'] ?? [];
        // remove links
        foreach ($headerLinks as $index => $link) {
            if (in_array($link[0]['rel'], [
                'revision',
                'delete-form',
                'edit-form',
                'shortlink',
                'forum-edit-form',
                'og-admin-routes',
                'forum-delete-form',
                'forum-edit-container-form',
                'version-history',
                'alternate'
            ])) {
                unset($headerLinks[$index]);
            }
        }
        $attachments['html_head_link'] = $headerLinks;
        return $attachments;
    }

    /*
     * Function that removes metaLinks
     * @param array $attachments
     * @return array $attachments
     */
    public function alterGeneralHeadMeta($attachments)
    {
        $headerMeta = $attachments['html_head'] ?? [];
        $currentRoute = \Drupal::service('current_route_match')->getRouteName();
        // remove head meta
        foreach ($headerMeta as $index => $meta) {
            // alter views forum
            $headerMeta[$index] = $this->alterViewsForumMetaTag($meta, $currentRoute);
            if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                'Generator'
            ])) {
                unset($headerMeta[$index]);
            }
            // truncate meta description to 150 characters => forum
            if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                'description'
            ])) {
                $description = strip_tags($headerMeta[$index][0]['#attributes']['content']);
                $headerMeta[$index][0]['#attributes']['content'] = Unicode::truncate($description, 150, true);
            }
            // truncate og description to 150 characters => forum
            if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                'og:description'
            ])) {
                $description = strip_tags($headerMeta[$index][0]['#attributes']['content']);
                $headerMeta[$index][0]['#attributes']['content'] = Unicode::truncate($description, 150, true);
            }
        }
        $attachments['html_head'] = $headerMeta;
        return $attachments;
    }

    /*
     * Function that adds the Html link elements rel next prev
     * @param array $attachments
     * @return array $attachment
     */
    public function addPaginationMeta($attachments)
    {
        global $pager_total, $base_url;
        if (empty($pager_total) || (isset($pager_total[0]) && $pager_total[0] == 0)) {
            return $attachments;
        }
        $currentUrl = $base_url . $this->request->getPathInfo();
        $headerLinks = $attachments['html_head_link'];
        $pagerCnt = isset($pager_total[0]) ? $pager_total[0] : 0;
        $removeCanonical = false;
        // add next
        if ($this->page > 0 && $this->page < $pagerCnt - 1) {
            $headerLinks[] = [
                [
                    'rel' => 'next',
                    'href' => $currentUrl . '?page=' . ($this->page + 1)
                ]
            ];
            $removeCanonical = true;
        }
        // add prev
        if ($this->page > 1 && $this->page < $pagerCnt) {
            $headerLinks[] = [
                [
                    'rel' => 'prev',
                    'href' => $currentUrl . '?page=' . ($this->page - 1)
                ]
            ];
            $removeCanonical = true;
        }

        // add prev
        if ($this->page == 1 && $this->page < $pagerCnt) {
            $headerLinks[] = [
                [
                    'rel' => 'prev',
                    'href' => $currentUrl
                ]
            ];
            $headerLinks[] = [
                [
                    'rel' => 'next',
                    'href' => $currentUrl . '?page=' . ($this->page + 1)
                ]
            ];
            $removeCanonical = true;
        }
        // add canonical
        if ($this->page == 0) {
            $headerLinks[] = [
                [
                    'rel' => 'canonical',
                    'href' => $currentUrl
                ]
            ];
        }
        if ($this->page == 0 && $this->page < $pagerCnt - 1) {
            $headerLinks[] = [
                [
                    'rel' => 'next',
                    'href' => $currentUrl . '?page=' . ($this->page + 1)
                ]
            ];
            $removeCanonical = true;
        }
        if ($removeCanonical) {
            // remove canonical
            foreach ($headerLinks as $index => $link) {
                if (in_array($link[0]['rel'], [
                    'canonical'
                ])) {
                    unset($headerLinks[$index]);
                }
            }
        }
        $attachments['html_head_link'] = $headerLinks;
        return $attachments;
    }

    /**
     * Function that modifies meta data of view /forum
     * Temporary awaiting metatag evolution
     *
     * @param unknown $attachments
     */
    public function alterViewsForumMetaTag($meta, $route)
    {
        switch ($route) {
            case 'forum.index':
                $description = 'Forum et discussions pour maigrir sans régime, perdre du poids sereinement et retrouver le plaisir de manger sans frustration | Linecoaching';
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                break;
            case 'view.temoignage.page_1':
                $description = 'Découvrez les témoignages des membres du programme Linecoaching  pour maigrir sans régime. Ils nous témoignent leurs expériences et leurs impressions';
                $title = 'Témoignages des membres de Linecoaching';
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                break;
            case 'view.ils_en_parle.page_2':
                // parole utilisateurs
                $description = 'Découvrez les avis des utilisateurs du programme Linecoaching pour maigrir sans régime. Ils ont testé la méthode et donnent leurs avis sur leurs parcours';
                $title = 'Avis des utilisateurs de Linecoaching';
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                break;
            case 'view.ils_en_parle.page_1':
                // parole utilisateurs
                $description = 'Les médias parlent de Linecoaching, découvrez les revues de la presse écrite, interviews, vidéos parlant de notre méthode pour maigrir sans régime';
                $title = 'Les médias parlent de Linecoaching';
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:description'
                ])) {
                    $meta[0]['#attributes']['content'] = $description;
                }
                if (! empty($meta[0]['#attributes']['name']) && in_array($meta[0]['#attributes']['name'], [
                    'title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                if (! empty($meta[0]['#attributes']['property']) && in_array($meta[0]['#attributes']['property'], [
                    'og:title'
                ])) {
                    $meta[0]['#attributes']['content'] = $title;
                }
                break;
            default:
        }
        return $meta;
    }
}
