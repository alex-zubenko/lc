<?php
namespace Drupal\mtc_core;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User profile entity entity.
 *
 * @see \Drupal\mtc_core\Entity\UserProfileEntity.
 */
class LcUserProfileEntityAccessControlHandler extends EntityAccessControlHandler
{

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\mtc_core\Entity\UserProfileEntityInterface $entity */
        switch ($operation) {
            case 'view':
                if ($account->isAuthenticated() && ($account->id() == $entity->getOwnerId())) {
                    return AccessResult::allowed();
                }
                if (! $entity->isPublished()) {
                    return AccessResult::allowedIfHasPermission($account, 'view unpublished user profile entity entities');
                }
                return AccessResult::allowedIf($account->isAuthenticated());
            case 'update':
                if ($account->isAuthenticated() && ($account->id() == $entity->getOwnerId())) {
                    return AccessResult::allowed();
                }
                return AccessResult::allowedIfHasPermission($account, 'edit user profile entity entities');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete user profile entity entities');
        }

        // Unknown operation, no opinion.
        return AccessResult::neutral();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'add user profile entity entities');
    }
}
