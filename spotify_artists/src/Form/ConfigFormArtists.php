<?php

namespace Drupal\spotify_artists\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Pager\PagerManager;
use Drupal\spotify_artists\SpotifyApiService;
use Drupal\spotify_artists\ArtistsService;
use Drupal\spotify_artists\SearchArtistsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements the artists config form controller.
 *
 * This implementation demonstrates using callbacks to add artist's ids to a
 * list.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class ConfigFormArtists extends ConfigFormBase {

  /**
   * Search artists service.
   *
   * @var \Drupal\spotify_artists\SearchArtistsService
   */

  protected SearchArtistsService $searchArtistsService;
  /**
   * Artists service.
   *
   * @var \Drupal\spotify_artists\ArtistsService
   */
  protected ArtistsService $artistsService;
  /**
   * API service.
   *
   * @var \Drupal\spotify_artists\SpotifyApiService
   */
  protected SpotifyApiService $spotifyApiService;
  /**
   * Pager service.
   *
   * @var \Drupal\Core\Pager\PagerManager
   */
  protected PagerManager $pagerManager;
  /**
   * API token.
   *
   * @var object|null
   */

  protected object|null $token;

  /**
   * Class constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SpotifyApiService $spotifyApiService,
    ArtistsService $artistsService,
    SearchArtistsService $searchArtistsService,
    PagerManager $pagerManager
    ) {
    parent::__construct($config_factory);
    $this->spotifyApiService = $spotifyApiService;
    $this->artistsService = $artistsService;
    $this->searchArtistsService = $searchArtistsService;
    $this->pagerManager = $pagerManager;
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection
   */
  public static function create(ContainerInterface $container): ConfigFormBase|ConfigFormArtists|static {
    return new static(
      $container->get('config.factory'),
      $container->get('spotify.api'),
      $container->get('spotify.artists'),
      $container->get('spotify.search'),
      $container->get('pager.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'spotify_artists.artists',
    ];
  }

  /**
   * Build form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Set and get token.
    $this->token = $this->spotifyApiService->spotifyApiToken();
    $form['artists_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Artists'),
      '#prefix' => '<div id="artists-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    ];

    $form['artists_fieldset']['artist'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search for an artist'),
      '#required' => FALSE,
    ];

    $options = $form_state->get('options');
    if (!empty($options)) {
      $form['artists_fieldset']['results'] = [
        '#type' => 'select',
        '#title' => $this->t('Now choose from these results'),
        '#limit_validation_errors' => [],
        '#options' => $options,
      ];

      $form['artists_fieldset']['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add selection to list'),
        '#submit' => ['::addArtistId'],
      ];
      $form['artists_fieldset']['actions']['clear'] = [
        '#type' => 'submit',
        '#value' => $this->t('Clear search'),
        '#limit_validation_errors' => [],
        '#submit' => ['::clearSearch'],
      ];

    }

    $form['artists_fieldset']['actions']['search'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => ['::searchArtist'],
    ];
    // Header for artists table.
    $header = [
      'artist_name' => $this->t('Name'),
      'artist_id' => $this->t('Id'),
    ];

    // Get artists data from Spotify.
    $artists = $this->artistsService->getArtists();
    // Create an array for the table.
    $list = [];
    if ($artists->status == 200) {
      foreach ($artists->artists as $key => $artist) {
        $list[$key] = [
          'artist_name' => Markup::create('<a href="/spotify_artist/' . $artist->id . '">' . $artist->name . '</a>'),
          'artist_id' => $artist->id,

        ];
      }

      // Create pager if more than x items.
      $total_count = count($list);
      $items_per_page = 10;
      $currentPage = $this->pagerManager->createPager($total_count, $items_per_page)->getCurrentPage();
      $chunks = array_chunk($list, $items_per_page);

      $form['artists_fieldset']['title'] = [
        '#type' => 'item',
        '#title' => $this->t('Delete artists'),
      ];
      $form['artists_fieldset']['table'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $chunks[$currentPage],
        '#empty' => $this->t('No artists found'),
      ];

      $form['artists_fieldset']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected items'),
        '#limit_validation_errors' => [],
        '#submit' => ['::deleteArtists'],
      ];

      $form['artists_fieldset']['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'spotify_artists_form_artists';
  }

  /**
   * Search artist function.
   */
  public function searchArtist(array &$form, FormStateInterface $form_state) {
    // Get value from search field.
    $query = $form_state->getValue('artist');
    // Call Search service and get results.
    $search_results = [];
    $artists = $this->searchArtistsService->searchArtists($query);
    if ($artists->status == 200) {
      $search_results = $artists->response->items;
    }

    if ($search_results) {
      // Use results in a select attribute.
      $options = [];
      foreach ($search_results as $artist) {
        $options[$artist->id] = $artist->name;
      }
      // Set options to form state.
      $form_state->set('options', $options);
    }
    else {
      $this->messenger()->addWarning(
              $this->t("No valid results, please try again")
            );
    }
    $form_state->setRebuild();
  }

  /**
   * Clear search.
   */
  public function clearSearch(array &$form, FormStateInterface $form_state) {
    $form_state
      ->set('options', [])
      ->setUserInput([]);
    $form_state->setRebuild();
  }

  /**
   * Add artist id.
   */
  public function addArtistId(array &$form, FormStateInterface $form_state) {
    // Get config.
    $config = $this->config('spotify_artists.artists');
    // Get current ids from config if any.
    $ids_in_config = $config->get('ids') ?? [];
    // Get selected result from form state.
    $selected_result = $form_state->getUserInput()['results'];
    // Add selected result to current ids array.
    $ids_in_config[] = $selected_result;
    // Set and save to config.
    $config->set('ids', $ids_in_config);
    $config->save();
    $this->messenger()->addMessage('Id added: ' . $selected_result);

    // Reset form options for a new search.
    $form_state
      ->set('options', [])
      ->setUserInput([]);
    $form_state->setRebuild();
  }

  /**
   * Delete artists.
   */
  public function deleteArtists(array $form, FormStateInterface $form_state) {
    // Get data from table of artists to delete.
    $delete_form = $form_state->getUserInput()['table'];
    // Create an array with deleted artists.
    $deleted_artists = [];
    foreach ($delete_form as $deleted_artist) {
      if ($deleted_artist !== NULL) {
        $deleted_artists[] = $form["artists_fieldset"]["table"]["#options"][$deleted_artist]["artist_id"];
      }
    }
    // Get current ids from config if any.
    $ids_in_config = $this
      ->config('spotify_artists.artists')
      ->get('ids');
    // Remove deleted artists from array.
    foreach ($deleted_artists as $id) {
      unset($ids_in_config[array_search($id, $ids_in_config)]);
    }
    // Reset array index.
    $sorted_ids = array_values($ids_in_config) ?? [];
    // Get an editable config.
    $config = $this->config('spotify_artists.artists');
    // Reset ids in config and save.
    $config
      ->clear('ids')
      ->set('ids', $sorted_ids);
    $config->save();
    // Reset form options for a new search.
    $form_state->set('options', [])->setUserInput([]);
    $form_state->setRebuild();
    $this->messenger()->addMessage($this->t('Ids deleted'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate search.
    $search_input = $form_state->getUserInput()['artist'];
    if (empty($search_input)) {
      $form_state->setErrorByName(
       'artist',
       $this->t("Enter an artist name please!")
      );
    }

    // Validate artists.
    $ids_in_config = $this
      ->config('spotify_artists.artists')
      ->get('ids');
    // Get selected result from form state.
    $selected_result = $form_state->getUserInput()['results'] ?? NULL;
    // Validate uniqueness.
    $count = count(array_keys($ids_in_config, $selected_result));
    if ($selected_result && $count !== 0) {
      $form_state->setErrorByName(
        'results',
        $this->t("This artist is not unique and already used in config, please make sure not to use the same artist more than once.")
      );
    }
    // Validate 20 artists limit.
    if (count($ids_in_config) == 20) {
      $form_state->setErrorByName(
      'results',
      $this->t("You've reached your limit of 20, please delete some in order to add more.")
      );
    }
  }

  /**
   * Final submit handler.
   *
   * Reports what values were finally set.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
