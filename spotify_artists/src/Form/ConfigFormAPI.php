<?php

namespace Drupal\spotify_artists\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spotify_artists\Service\SpotifyApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * Form with API credentials.
   *
   * See more: https://developer.spotify.com/documentation/web-api/tutorials/getting-started#create-an-app.
   */
  public function __construct(ConfigFactoryInterface $config_factory, public SpotifyApiService $spotifyApiService) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ConfigFormAPI|ConfigFormBase|static {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('config.factory'),
      $container->get('spotify.api')
    );
  }

  /**
   * Settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('spotify_artists.api');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('Set Spotify API here. You may validate before saving.'),
    ];

    $form['settings_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('API settings'),
      '#prefix' => '<div id="settings-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    ];

    $form['settings_fieldset']['valid_info'] = [
      '#type' => 'item',
      '#prefix' => '<div id="valid-info">',
      '#suffix' => '</div>',
    ];

    $form['settings_fieldset']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id') ?: getenv('SPOTIFY_CLIENT_ID'),
    ];

    $form['settings_fieldset']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_secret') ?: getenv('SPOTIFY_CLIENT_SECRET'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    $form['actions']['verify'] = [
      '#type' => 'submit',
      '#value' => $this->t('Verify'),
      '#submit' => ['::verifyApi'],
      '#ajax' => [
        'callback' => '::verifyApi',
        'event' => 'click',
        'wrapper' => 'valid-info',
      ],
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
   * Option to verify API credentials.
   */
  public function verifyApi(array &$form): array {
    $response = $this->spotifyApiService->accessWithCodeAuthorization($form["settings_fieldset"]["client_id"]["#value"], $form["settings_fieldset"]["client_secret"]["#value"]);
    if ($response['status'] == 200) {
      $message = $this->t("API valid, you may save these credentials");
      $class = 'color-success';
    }
    else {
      $message = $this->t("API not valid, please check these credentials");
      $class = 'color-error ';
    }
    $output = "<div id='valid-info'><span class='$class'>$message</span></div>";

    // Return the HTML markup we built above in a render array.
    return ['#markup' => $output];
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
