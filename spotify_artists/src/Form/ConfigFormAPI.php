<?php

namespace Drupal\spotify_artists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the ajax demo form controller.
 *
 * This example demonstrates using ajax callbacks to add people's ids to a
 * list of picnic attendees.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigFormAPI extends ConfigFormBase {

  /**
   * Form with 'add more' and 'remove' buttons.
   *
   * This example shows a button to "add more" - add another textfield, and
   * the corresponding "remove" button.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('spotify_artists.api');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This example shows an add-more and a remove-last button.'),
    ];

    $form['settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('API settings'),
      '#prefix' => '<div id="settings-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    ];

    $form['settings_fieldset']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id'),
    ];

    $form['settings_fieldset']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_secret'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'spotify_artists_form_api';
  }

  /**
   * Validate form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Final submit handler.
   *
   * Reports what values were finally set.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('spotify_artists.api');
    $config->set('client_id', $form_state->getValue('client_id'));
    $config->set('client_secret', $form_state->getValue('client_secret'));
    $config->save();
    $form_state->setRebuild();
    $this->messenger()->addMessage($this->t("Settings saved"));
  }

  /**
   * GetEditableConfigNames.
   */
  protected function getEditableConfigNames(): array {
    return [
      'spotify_artists.api',
    ];
  }

}
