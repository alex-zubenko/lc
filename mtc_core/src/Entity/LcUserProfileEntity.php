<?php
namespace Drupal\mtc_core\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Lc User profile entity entity.
 *
 * @ingroup mtc_core
 *
 * @ContentEntityType(
 * id = "lc_user_profile_entity",
 * label = @Translation("MTC Core User profile entity"),
 * handlers = {
 * "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 * "list_builder" = "Drupal\mtc_core\LcUserProfileEntityListBuilder",
 * "views_data" = "Drupal\mtc_core\Entity\LcUserProfileEntityViewsData",
 *
 * "form" = {
 * "default" = "Drupal\mtc_core\Form\LcUserProfileEntityForm",
 * "add" = "Drupal\mtc_core\Form\LcUserProfileEntityForm",
 * "edit" = "Drupal\mtc_core\Form\LcUserProfileEntityForm",
 * "delete" = "Drupal\mtc_core\Form\LcUserProfileEntityDeleteForm",
 * },
 * "access" = "Drupal\mtc_core\LcUserProfileEntityAccessControlHandler",
 * "route_provider" = {
 * "html" = "Drupal\mtc_core\LcUserProfileEntityHtmlRouteProvider",
 * },
 * },
 * base_table = "lc_user_profile_entity",
 * admin_permission = "administer lc user profile entity entities",
 * entity_keys = {
 * "id" = "user_id",
 * "label" = "title",
 * "uuid" = "uuid",
 * "uid" = "user_id",
 * },
 * links = {
 * "canonical" = "/mon-profil/{lc_user_profile_entity}",
 * "add-form" = "/mon-profil/add",
 * "edit-form" = "/mon-profil/{lc_user_profile_entity}/edit",
 * "delete-form" = "/mon-profil/{lc_user_profile_entity}/delete",
 * "collection" = "/admin/structure/lc_user_profile_entity",
 * },
 * field_ui_base_route = "lc_user_profile_entity.settings"
 * )
 */
class LcUserProfileEntity extends ContentEntityBase implements LcUserProfileEntityInterface
{

    use EntityChangedTrait;

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values)
    {
        parent::preCreate($storage_controller, $values);
        $values += array(
            'user_id' => \Drupal::currentUser()->id()
        );
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getTitle()
    {
        return $this->get('title')->value;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function setTitle($title)
    {
        $this->set('title', $title);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getCreatedTime()
    {
        return $this->get('created')->value;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function setCreatedTime($timestamp)
    {
        $this->set('created', $timestamp);
        return $this;
    }
    /**
     *
     * {@inheritdoc}
     *
     */
    public function getSigntaure()
    {
        return $this->get('signature')->value;
    }
    /**
     *
     * {@inheritdoc}
     *
     */
    public function getOwner()
    {
        return $this->get('user_id')->entity;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getOwnerId()
    {
        return $this->get('user_id')->target_id;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function setOwnerId($uid)
    {
        $this->set('user_id', $uid);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function setOwner(UserInterface $account)
    {
        $this->set('user_id', $account->id());
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function isPublished()
    {
        return (bool) $this->get('status')->value;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function setPublished($published)
    {
        $this->set('status', $published ? TRUE : FALSE);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')->setLabel(t('Authored by'))
            ->setDescription(t('The user ID of author of the User profile entity entity.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'author',
            'weight' => 0
        ))
            ->setReadOnly(TRUE);
        $fields['surname'] = BaseFieldDefinition::create('string')->setLabel('Nom')
            ->setDescription('Votre Nom')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => - 4
        ))
            ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => - 4
        ));
        $fields['sexe'] = BaseFieldDefinition::create('list_string')->setLabel('Sexe')
            ->setSetting('unsigned', TRUE)
            ->setSetting('allowed_values', [
            'Femme',
            'Homme'
        ])
            ->setDisplayOptions('form', array(
            'type' => 'options_select',
            'weight' => - 2
        ))
            ->setDisplayConfigurable('form', TRUE);
        $fields['civility'] = BaseFieldDefinition::create('list_string')->setLabel('Civilite')
            ->setSetting('unsigned', TRUE)
            ->setSetting('allowed_values', [
            'Mademoiselle',
            'Madame',
            'Monsieur'
        ])
            ->setDisplayOptions('form', array(
            'type' => 'options_select',
            'weight' => - 2
        ))
            ->setDisplayConfigurable('form', TRUE);
        $fields['title'] = BaseFieldDefinition::create('string')->setLabel('Titre')
            ->setDescription('Le titre de votre profile')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => - 4
        ))
            ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        $fields['ville'] = BaseFieldDefinition::create('string')->setLabel('ville')
            ->setDescription('Pays')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => - 4
        ))
            ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        $fields['country'] = BaseFieldDefinition::create('string')->setLabel('Pays')
            ->setDescription('Pays')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => - 4
        ))
            ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        $fields['birthday'] = BaseFieldDefinition::create('datetime')->setLabel('Date de naissance')
            ->setDescription('Date de naissance')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'datetime_default',
            'settings' => array(
                'display_label' => TRUE
            ),
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        $fields['telephone'] = BaseFieldDefinition::create('telephone')->setLabel('Telephone')
            ->setDescription('Votre Telephone')
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'telephone_default',
            'settings' => array(
                'display_label' => TRUE
            ),
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);
        $fields['status'] = BaseFieldDefinition::create('boolean')->setLabel('Rendre active / inactive votre profile')
            ->setDescription('Rendre active / inactive votre profile')
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'boolean_checkbox',
            'settings' => array(
                'display_label' => TRUE
            ),
            'weight' => 16
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Created'))->setDescription(t('The time that the entity was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Changed'))->setDescription(t('The time that the entity was last edited.'));

        $fields['profile_experience'] = BaseFieldDefinition::create('text_long')->setLabel('Ma présentation et mes motivations')
            ->setDescription('Ma présentation et mes motivations')
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'text_default',
            'weight' => 0
        ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'text_textfield',
            'weight' => 0
        ))
            ->setDisplayConfigurable('form', TRUE);

        $fields['profile_mon_histoire'] = BaseFieldDefinition::create('text_long')->setLabel('Mon histoire')
            ->setDescription('Mon histoire')
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'text_default',
            'weight' => 0
        ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'text_textfield',
            'weight' => 0
        ))
            ->setDisplayConfigurable('form', TRUE);

        $fields['profile_conseils'] = BaseFieldDefinition::create('text_long')->setLabel('Mes conseils à partager')
            ->setDescription('Mes conseils à partager')
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'text_default',
            'weight' => 0
        ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'text_textfield',
            'weight' => 0
        ))
            ->setDisplayConfigurable('form', TRUE);

        $fields['profile_interet'] = BaseFieldDefinition::create('text_long')->setLabel("Mes centres d'intérêt")
            ->setDescription("Mes centres d'intérêt")
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'text_default',
            'weight' => 0
        ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'text_textfield',
            'weight' => 0
        ))
            ->setDisplayConfigurable('form', TRUE);

        $fields['profile_blog_title'] = BaseFieldDefinition::create('string')->setLabel('Titre de votre blog')
            ->setDescription('Titre de votre blog')
            ->setSettings(array(
            'max_length' => 255,
            'text_processing' => 0
        ))
            ->setDefaultValue('')
            ->setDisplayOptions('view', array(
            'label' => 'above',
            'type' => 'string',
            'weight' => - 4
        ))
            ->setDisplayOptions('form', array(
            'type' => 'string_textfield',
            'weight' => - 4
        ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['profile_blog_description'] = BaseFieldDefinition::create('text_long')->setLabel('Description de votre blog')
            ->setDescription('Description de votre blog')
            ->setDisplayOptions('view', array(
            'label' => 'hidden',
            'type' => 'text_default',
            'weight' => 0
        ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
            'type' => 'text_textfield',
            'weight' => 0
        ))
            ->setDisplayConfigurable('form', TRUE);
        $fields['signature'] = BaseFieldDefinition::create('text_long')->setLabel('Signature')
            ->setDisplayOptions('view', array(
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 0
            ))
            ->setDisplayConfigurable('view', TRUE)
            ->setDisplayOptions('form', array(
                'type' => 'text_textfield',
                'weight' => 0
            ))
            ->setDisplayConfigurable('form', TRUE);
        return $fields;
    }
}
