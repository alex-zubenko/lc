<?php

/**
 * @file
 * Contains \Drupal\current_page_crumb\BreadcrumbBuilder.
 */
namespace Drupal\mtc_core\Builder;

use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\TermStorage;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Adds the current page title to the breadcrumb.
 *
 * Extend PathBased Breadcrumbs to include the current page title as an unlinked
 * crumb. The module uses the path if the title is unavailable and it excludes
 * all admin paths.
 *
 * {@inheritdoc}
 *
 */
class MtcCoreBreadcrumbBuilder implements BreadcrumbBuilderInterface
{
    use StringTranslationTrait;

    /**
     * The entity manager.
     *
     * @var \Drupal\Core\Entity\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * The taxonomy storage.
     *
     * @var \Drupal\Taxonomy\TermStorageInterface
     */
    protected $termStorage;

    /**
     * Current route
     *
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected $route;

    /**
     * Current route
     *
     * @var \Drupal\Core\Breadcrumb\Breadcrumb
     */
    protected $breadcrumb;

    /**
     * Constructs the TermBreadcrumbBuilder.
     *
     * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
     *            The entity manager.
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->termStorage = $entityManager->getStorage('taxonomy_term');
        $this->route = \Drupal::routeMatch();
        $this->breadcrumb = new Breadcrumb();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function applies(RouteMatchInterface $route_match)
    {
        // apply only to front
        return ! \Drupal::service('router.admin_context')->isAdminRoute();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build(RouteMatchInterface $route_match)
    {
        // node construction
        if ($node = $this->route->getParameter('node')) {
            return $this->buildNodeBreadCrumb($node);
        }
        // view construction
        if ($this->route->getParameter('view_id')) {
            $routeObject = $route_match->getRouteObject();
            return $this->buildViewBreadCrumb($routeObject);
        }
        // term construction
        if ($term = $this->route->getParameter('taxonomy_term')) {
            return $this->buildTaxonomyBreadCrumb($term);
        }
        if($route_match->getRouteName() == 'forum.index') {
            return $this->buildHomeForumBreadCrumb();
        }
        return $this->buildDefaultBreadCrumb();
    }

    public function buildHomeForumBreadCrumb() {
        $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
        $this->breadcrumb->addCacheContexts([
          'route',
        ]);
        return $this->breadcrumb;
    }

    /**
     * Build bread crumb for nodes
     *
     * @param \Drupal\taxonomy\Entity\Term $term
     * @return \Drupal\Core\Breadcrumb\Breadcrumb $breadcrumb
     */
    public function buildTaxonomyBreadCrumb(\Drupal\taxonomy\Entity\Term $term)
    {
        if ($term->getVocabularyId() == 'forums') {
            $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
            $this->breadcrumb->addLink(Link::createFromRoute(t('Forum'), 'forum.index'));
            $this->breadcrumb->addCacheableDependency($term);
            $this->breadcrumb->addCacheContexts([
              'route',
            ]);
            return $this->breadcrumb;
        }
        else {
            $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
            // Breadcrumb needs to have terms cacheable metadata as a cacheable
            // dependency even though it is not shown in the breadcrumb because e.g. its
            // parent might have changed.
            $this->breadcrumb->addCacheableDependency($term);
            $vid = $term->getVocabularyId();
            if ($vid == 'tag_dossier') {
                $parentTheme = $term->get('field_parent_theme')->getValue();
                $parentTheme = isset($parentTheme[0]['target_id']) ? $parentTheme[0]['target_id'] : NULL;
                $parentTheme = isset($parentTheme) ? \Drupal\taxonomy\Entity\Term::load($parentTheme) : NULL;
                $term = $parentTheme ?? $term;
            }
            // vocabulary or term.
            $parents = $this->termStorage->loadAllParents($term->id());
            // Remove current term being accessed.
            $currentTerm = array_shift($parents);
            foreach (array_reverse($parents) as $term) {
                $term = $this->entityManager->getTranslationFromContext($term);
                $this->breadcrumb->addCacheableDependency($term);
                $this->breadcrumb->addLink(Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', [
                  'taxonomy_term' => $term->id(),
                ]));
            }

            // This breadcrumb builder is based on a route parameter, and hence it
            // depends on the 'route' cache context.
            $this->breadcrumb->addCacheContexts([
              'route',
            ]);
            // /add current node url
            $this->breadcrumb->addLink(Link::createFromRoute($currentTerm->getName(), '<none>'));
            return $this->breadcrumb;
        }
    }

    /**
     * Build bread crumb for nodes
     *
     * @param \Drupal\node\Entity\Node $node
     * @return \Drupal\Core\Breadcrumb\Breadcrumb
     */
    public function buildNodeBreadCrumb(\Drupal\node\Entity\Node $node)
    {
        $this->breadcrumb = new Breadcrumb();

        // By setting a "cache context" to the "url", each requested URL gets it's own cache.
        // This way a single breadcrumb isn't cached for all pages on the site.
        $this->breadcrumb->addCacheContexts([
            "url"
        ]);

        // By adding "cache tags" for this specific node, the cache is invalidated when the node is edited.
        $this->breadcrumb->addCacheTags([
            "node:{$node->nid->value}"
        ]);

        $blog_post = $node->getType();
        // Add "Home" breadcrumb link.
        $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        // check taxonomy terms
        $termForum = $node->hasField('taxonomy_forums') ? $node->get('taxonomy_forums')->getValue() : NULL;
        $termTheme = $node->hasField('field_theme') ? $node->get('field_theme')->getValue() : NULL;
        if ($termTheme) {
            $tid = $termTheme[0]['target_id'];
            $parents = $this->termStorage->loadAllParents($tid);
            foreach (array_reverse($parents) as $term) {
                $term = $this->entityManager->getTranslationFromContext($term);
                $this->breadcrumb->addCacheableDependency($term);
                $this->breadcrumb->addLink(Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', [
                    'taxonomy_term' => $term->id()
                ]));
            }
        }
        if ($termForum) {
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
            $this->breadcrumb->addLink(Link::createFromRoute(t('Forum'), 'forum.index'));
            $tid = $termForum[0]['target_id'];
            $parents = $this->termStorage->loadAllParents($tid);
            /** @var \Drupal\taxonomy\Entity\Term $term */
            foreach (array_reverse($parents) as $term) {
                $parrent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
                if(empty($parrent)) {
                    continue;
                }
                $term = $this->entityManager->getTranslationFromContext($term);
                $this->breadcrumb->addCacheableDependency($term);
                $this->breadcrumb->addLink(Link::createFromRoute($term->getName(), 'entity.taxonomy_term.canonical', [
                    'taxonomy_term' => $term->id()
                ]));
            }
        }
        // if not part of taxonomy,use main hierarchy
        if (empty($termTheme) && empty($termForum) && $blog_post != 'blog_post') {
            $nodeId = $node->id();
            $menuLinkManager = \Drupal::service('plugin.manager.menu.link');
            $trailIds = \Drupal::service('menu.active_trail')->getActiveTrailIds('main');
            $menuLink = $menuLinkManager->loadLinksByRoute('entity.node.canonical', [
                'node' => $nodeId
            ]);
            $menuLink = is_array($menuLink) ? array_shift($menuLink) : NULL;
            $currentPluginId = isset($menuLink) ? $menuLink->getPluginId() : NULL;
            foreach (array_reverse($trailIds) as $key => $value) {
                if ($value && $value !== $currentPluginId) {
                    $this->breadcrumb->addLink(new Link($menuLinkManager->createInstance($value)
                        ->getTitle(), $menuLinkManager->createInstance($value)
                        ->getUrlObject()));
                }
            }
        }
        if ($blog_post == 'chat_groups') {
          $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
          $this->breadcrumb->addLink(Link::fromTextAndUrl('Chats de groupes', Url::fromUri('base:/og')));
          $this->breadcrumb->addLink(Link::createFromRoute($node->getTitle(), '<none>'));
        }
        elseif ($blog_post == 'temoignages') {
          $this->breadcrumb->addLink(Link::fromTextAndUrl('Ils en parlent', Url::fromUri('base:/ils-en-parlent')));
          $this->breadcrumb->addLink(Link::fromTextAndUrl('Témoignages', Url::fromUri('base:/maigrir/methode/temoignages/tanetane')));
          $this->breadcrumb->addLink(Link::createFromRoute($node->getTitle(), '<none>'));
        }
        elseif ($blog_post == 'livre_des_experts') {
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Méthode', Url::fromUri('base:/methode-maigrir-sans-regime'))
          );
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Experts et coachs', Url::fromUri('base:/maigrir/nos-experts/nos-experts-maigrir-sans-regime'))
          );
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Livres', Url::fromUri('base:/maigrir/nos-experts/maigrir-cest-dans-la-tete'))
          );
          $this->breadcrumb->addLink(Link::createFromRoute($node->getTitle(), '<none>'));
        }
        elseif (!$termForum && $blog_post != 'blog_post') {
            $this->breadcrumb->addLink(Link::createFromRoute($node->getTitle(), '<none>'));
        }
        if ($blog_post == 'blog_post') {
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t("Member's blog"), Url::fromUri('base:/blog')));
        }
        return $this->breadcrumb;
    }

    /**
     * Build bread crumb for views
     *
     * @param \Symfony\Component\Routing\Route $route
     * @return \Drupal\Core\Breadcrumb\Breadcrumb
     */
    public function buildViewBreadCrumb(\Symfony\Component\Routing\Route $route)
    {
        $routeDefaults = $route->getDefaults();
        // By setting a "cache context" to the "url", each requested URL gets it's own cache.
        // This way a single breadcrumb isn't cached for all pages on the site.
        $this->breadcrumb->addCacheContexts([
            "url"
        ]);
        // By adding "cache tags" for this specific node, the cache is invalidated when the node is edited.
        $this->breadcrumb->addCacheTags([
            "view:" . $routeDefaults['view_id'] . $routeDefaults['display_id']
        ]);
        $currentRoute = \Drupal::routeMatch()->getRouteName();
        $r = $route->getPath();
        // Add "Home" breadcrumb link.
        $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
        if ($currentRoute == 'view.temoignage.page_1' && $route->getPath() == '/maigrir/la-communaute/temoignages') {
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Ils en parlent', Url::fromUri('base:/ils-en-parlent'))
          );
          $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        elseif ($currentRoute == 'view.livres_des_experts.page_1' && $route->getPath() == '/maigrir/livres/livres-des-experts') {
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Méthode', Url::fromUri('base:/methode-maigrir-sans-regime'))
          );
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Experts et coachs', Url::fromUri('base:/maigrir/nos-experts/nos-experts-maigrir-sans-regime'))
          );
          $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        elseif ($currentRoute == 'view.ils_en_parle.page_1' && $route->getPath() == '/maigrir/methode/maigrir-sans-regime-revue-presse') {
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Ils en parlent', Url::fromUri('base:/ils-en-parlent'))
          );
          $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        elseif ($currentRoute == 'view.ils_en_parle.page_2' && $route->getPath() == '/paroles-utilisateurs') {
          $this->breadcrumb->addLink(
            Link::fromTextAndUrl('Ils en parlent', Url::fromUri('base:/ils-en-parlent'))
          );
          $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        elseif ($currentRoute == 'view.chat_historique.page_1' || $route->getPath() == '/maigrir/la-communaute/temoignages' || $route->getPath() == '/maigrir/la-communaute/chat-historique' ) {

          $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
          if ($route->getPath() == '/maigrir/la-communaute/chat-historique') {
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Chat avec les experts'), Url::fromUri('base:/maigrir/la-communaute/chat-direct')));
          }
          $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        elseif ($currentRoute == 'view.blog.blog_all' && $route->getPath() == '/blog') {
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
        }
        elseif ($currentRoute == 'view.blog.blog_user_all' && $route->getPath() == '/blog/{arg_0}') {
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
            $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t("Member's blog"), Url::fromUri('base:/blog')));
        }
        elseif ($route->getPath() != '/maigrir/la-communaute/temoignages'&& $route->getPath() !='/paroles-utilisateurs') {
            // /add current view title
            $this->breadcrumb->addLink(Link::createFromRoute($routeDefaults['_title'], '<none>'));
        }
        return $this->breadcrumb;
    }

    /**
     * Build default breadcrumbs
     *
     * @return \Drupal\Core\Breadcrumb\Breadcrumb
     */
    public function buildDefaultBreadCrumb()
    {

        // By setting a "cache context" to the "url", each requested URL gets it's own cache.
        // This way a single breadcrumb isn't cached for all pages on the site.
        $this->breadcrumb->addCacheContexts([
            "url"
        ]);

        // By adding "cache tags" for this specific node, the cache is invalidated when the node is edited.
        $this->breadcrumb->addCacheTags([
            "node:default"
        ]);
      $currentRoute = \Drupal::routeMatch()->getRouteName();
        // Add "Home" breadcrumb link.
        $this->breadcrumb->addLink(Link::createFromRoute($this->t('Home'), '<front>'));
      if ($currentRoute == 'mtc_core.chat_nasteo') {
        $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
      }
      if ($currentRoute == 'mtc_core.og_group_chat_group_home') {
        $this->breadcrumb->addLink(Link::fromTextAndUrl($this->t('Community'), Url::fromUri('base:/maigrir/la-communaute')));
      }
        return $this->breadcrumb;
    }
}
