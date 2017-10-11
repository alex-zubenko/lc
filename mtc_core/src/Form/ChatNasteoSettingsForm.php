<?php
namespace Drupal\mtc_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class ChatNasteoSettingsForm.
 *
 * @package Drupal\mtc_core\Form
 */
class ChatNasteoSettingsForm extends ConfigFormBase
{

    /**
     * use Drupal\Core\Form\ConfigFormBase;
     *
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return 'chat_nasteo_settings_form';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('mtc_core.chatnasteo.settings');
        $body = $config->get('chatnasteo.body');
        $form['header'] = [
            '#type' => 'markup',
            '#markup' => '<h1>Chat Nasteo settings</h1>'
        ];
        $form['title'] = [
            '#type' => 'textfield',
            '#title' => 'Titre',
            '#required' => TRUE,
            '#default_value' => $config->get('chatnasteo.title')
        ];
        $form['body'] = [
            '#type' => 'text_format',
            '#title' => $this->t('body'),
            '#default_value' => $body['value'] ?? ''
        ];
        $form['salon'] = [
            '#type' => 'number',
            '#title' => $this->t('Numero de salon'),
            '#required' => TRUE,
            '#default_value' => $config->get('chatnasteo.salon')

        ];
        $form['photo_expert'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Photo d\'expert'),
            '#default_value' => $config->get('chatnasteo.photo_expert'),
            '#upload_location' => 'public://images/',
        ];
        return parent::buildForm($form, $form_state);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $salon = (int) $form_state->getValue('salon');
        $title = $form_state->getValue('title');
        if (empty($salon) || ! is_int($salon)) {
            $form_state->setErrorByName('salon', 'Veuillez saisir le numero de salon chat nasteo.');
        }
        if (empty($title)) {
            $form_state->setErrorByName('title', 'Veuillez saisir le titre de chat nasteo.');
        }
        parent::validateForm($form, $form_state);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Retrieve the configuration
        $this->config('mtc_core.chatnasteo.settings')
            ->set('chatnasteo.salon', $form_state->getValue('salon'))
            ->set('chatnasteo.title', $form_state->getValue('title'))
            ->set('chatnasteo.body', $form_state->getValue('body'))
            ->set('chatnasteo.photo_expert', $form_state->getValue('photo_expert'))
            ->save();
           //set file save permanent
            $image = $form_state->getValue('photo_expert');
            $file = \Drupal\file\Entity\File::load( $image[0] );
            /* Set the status flag permanent of the file object */
            $file->setPermanent();
            /* Save the file in database */
            $file->save();
        return parent::submitForm($form, $form_state);
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    protected function getEditableConfigNames()
    {
        return [
            'mtc_core.chatnasteo.settings'
        ];
    }
}
