<?php
namespace Drupal\mtc_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a footer content for mtc_core
 * Containts inscription newlsetter form,social media links,
 * and main menu navigation tree
 *
 * @Block(
 * id = "lc_footer_menu_block",
 * admin_label = @Translation("MTC Core footer menu block"),
 * category = @Translation("MTC Core Custom block")
 * )
 */
class LcFooterMenuBlock extends BlockBase
{

    /**
     *
     * {@inheritdoc}
     *
     */
    public function build()
    {
        $build = [];
        $facebook_url = theme_get_setting('facebook_url');
        $twitter_url = theme_get_setting('twitter_url');
        // main menu tree
        $build['items'] = $this->generateTree();
        // social account set via themes settings
        $build['facebook_url'] = $facebook_url;
        $build['twitter_url'] = $twitter_url;
        // get footer newsletter block
        $newsLetterForm = \Drupal::service('form_builder')->getForm('Drupal\mtc_core\Form\SubscriptionNewsletterForm');
        $build['newsletter_footer'] = drupal_render($newsLetterForm);
        return $build;
    }

    /**
     * Function generates the footer menu from navigation main_menu
     */
    public function generateTree()
    {
        $menu_tree = \Drupal::menuTree();
        $menu_name = 'main';
        $level = isset($this->configuration['level']) ? (int) $this->configuration['level'] : 2;

        // Build the typical default set of menu tree parameters.
        $parameters = new MenuTreeParameters();
        $parameters->setMaxDepth($level);
        $parameters->setMinDepth(1);
        // Load the tree based on this set of parameters.
        $tree = $menu_tree->load($menu_name, $parameters);

        // Transform the tree using the manipulators you want.
        $manipulators = [
            // Only show links that are accessible for the current user.
            [
                'callable' => 'menu.default_tree_manipulators:checkAccess'
            ],
            // Use the default sorting of menu links.
            [
                'callable' => 'menu.default_tree_manipulators:generateIndexAndSort'
            ]
        ];
        $tree = $menu_tree->transform($tree, $manipulators);
        // Finally, build a renderable array from the transformed tree.
        $menu = $menu_tree->build($tree);

        return $menu['#items'];
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockForm($form, FormStateInterface $form_state)
    {
        $form['level'] = [
            '#type' => 'number',
            '#title' => $this->t('Level'),
            '#description' => $this->t(''),
            '#default_value' => isset($this->configuration['level']) ? $this->configuration['level'] : 2,
            '#weight' => '3'
        ];
        return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function blockSubmit($form, FormStateInterface $form_state)
    {
        $this->configuration['level'] = $form_state->getValue('level');
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('level') < 1 || $form_state->getValue('level') > 5) {
            $form_state->setErrorByName('level', $this->t('Please enter a number between 1 and 5'));
        }
    }
}
