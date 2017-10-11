<?php
namespace Drupal\mtc_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class BilanGratuitForm.
 *
 * @package Drupal\mtc_core\Form
 */
class BilanGratuitForm extends FormBase
{

    /**
     *
     * {@inheritdoc}
     *
     */
    public function getFormId()
    {
        return 'bilan_gratuit_form';
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#method'] = 'POST';
        $form['#action'] = theme_get_setting('bilan_site');
        $form['#attributes']['target'] = '_self';

        $form['form_introduction_text'] = [
            '#prefix' => '<div class="form_introduction_text">',
            '#suffix' => '</div>',
            '#markup' => '<p>Votre bilan gratuit en 4 min</p>',
            '#attributes' => [
                'class' => [
                    'form_introduction_text'
                ]
            ]
        ];
        $form['age'] = [
            '#type' => 'select',
            '#title' => $this->t('Votre âge'),
            '#description' => $this->t('Votre âge'),
            '#options' => array_combine(range(18,99),range(18,99)),
            '#required' => TRUE,
            '#default_value' => 18,
            '#attributes' => [
                'class' => [
                    'age  chosen-disable'
                ]
            ]
        ];
        $form['size'] = [
            '#type' => 'number',
            '#title' => $this->t('Votre taille'),
            '#description' => $this->t('Votre taille en cm'),
            '#required' => TRUE,
            '#min' => 120,
            '#max' => 200,
            '#default_value' => '',
            '#attributes' => [
                'class' => [
                    'size'
                ]
            ]
        ];
        $form['weight'] = [
            '#type' => 'number',
            '#title' => $this->t('Votre poids'),
            '#description' => 'Votre poids en kg',
            '#required' => TRUE,
            '#min' => 40,
            '#max' => 200,
            '#default_value' => '',
            '#attributes' => [
                'class' => [
                    'weight'
                ]
            ]
        ];
        $form['sexe'] = [
            '#type' => 'radios',
            '#title' => $this->t('Vous êtes'),
            '#description' => $this->t('Femme ou Homme'),
            '#options' => [
                '0' => 'une femme',
                '1' => 'un homme'
            ],
            '#required' => TRUE,
            '#default_value' => 0,
            '#attributes' => [
                'class' => [
                    'sexe'
                ]
            ]
        ];

        $form['submit'] = [
            '#type' => 'submit',
            '#value' => "Je démarre mon bilan",
            '#attributes' => [
                'class' => [
                    'submit'
                ]
            ]
        ];
        return $form;
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->getValue('weight') < 40 || $form_state->getValue('weight') > 200) {
            $form_state->setErrorByName('weight', 'Votre poids doit être compris entre 40 kg et 200 kg.');
        }
        if ($form_state->getValue('size') < 120 || $form_state->getValue('size') > 200) {
            $form_state->setErrorByName('size', 'Votre taille doit être comprise entre 120 cm et 200 cm.');
        }
    }

    /**
     *
     * {@inheritdoc}
     *
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {}
}
