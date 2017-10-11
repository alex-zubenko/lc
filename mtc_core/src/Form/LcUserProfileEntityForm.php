<?php
namespace Drupal\mtc_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\linecoaching\Entity\LcUserProfileEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form controller for User profile entity edit forms.
 *
 * @ingroup mtc_core
 */
class LcUserProfileEntityForm extends ContentEntityForm
{

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        /* @var $entity \Drupal\mtc_core\Entity\UserProfileEntity */
        $form = parent::buildForm($form, $form_state);

        $entity = $this->entity;
        if (($profile = LcUserProfileEntity::load(\Drupal::currentUser()->id())) && \Drupal::routeMatch()->getRouteName() == 'entity.lc_user_profile_entity.add_form') {
          return new RedirectResponse(\Drupal::url('entity.lc_user_profile_entity.edit_form', ['lc_user_profile_entity' => $profile->id()]));
        }

        return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $entity = &$this->entity;
        // save avatar image if set
        $avatarFid = $form_state->getValue('select_avatar');
        if (null !== $avatarFid) {
            $owner = $entity->getOwner();
            $owner->user_picture->setValue([
                "target_id" => $avatarFid
            ]);
            $owner->save();
        }

        $status = parent::save($form, $form_state);

        switch ($status) {
            case SAVED_NEW:
                drupal_set_message('Votre profil a été créer');
                break;

            default:
                drupal_set_message('Votre profil a été sauvegardé');
        }
        $form_state->setRedirect('entity.lc_user_profile_entity.canonical', [
            'lc_user_profile_entity' => $entity->id()
        ]);
    }
}
