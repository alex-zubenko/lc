<?php
namespace Drupal\mtc_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of User profile entity entities.
 *
 * @ingroup mtc_core
 */
class LcUserProfileEntityListBuilder extends EntityListBuilder
{

    use LinkGeneratorTrait;

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildHeader()
    {
        $header['id'] = $this->t('User profile entity ID');
        $header['name'] = $this->t('Name');
        return $header + parent::buildHeader();
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildRow(EntityInterface $entity)
    {
        /* @var $entity \Drupal\mtc_core\Entity\UserProfileEntity */
        $row['id'] = $entity->id();
        $name = $entity->getOwner() ? $entity->getOwner()->getAccountName() : '';
        $row['name'] = $this->l($name, new Url('entity.user.edit_form', array(
            'user' => $entity->id()
        )));
        return $row + parent::buildRow($entity);
    }
}
