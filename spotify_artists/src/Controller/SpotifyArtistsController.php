<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\spotify_artists\ArtistsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\spotify_artists\SpotifyApiService;

/**
 * Provides route responses for the Spotify artists module.
 */
class SpotifyArtistsController extends ControllerBase {

  /**
   * Returns a page based on id from url.
   *
   * @return array
   * A simple renderable array with artist object.
   */
  /**
   * API service.
   *
   * @var \Drupal\spotify_artists\SpotifyApiService
   */
  public SpotifyApiService $spotify;

  /**
   * Artists service.
   *
   * @var \Drupal\spotify_artists\ArtistsService
   */
  protected ArtistsService $artistsService;

  /**
   * Config data.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */

  protected ConfigFactoryInterface $config;

  /**
   * API token.
   *
   * @var object|null
   */

  protected ?object $token;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *
   *   Config data.
   * @param \Drupal\spotify_artists\SpotifyApiService $spotify
   *
   *   Config data.
   * @param \Drupal\spotify_artists\ArtistsService $artistsService
   *   Config data.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              SpotifyApiService $spotify,
                              ArtistsService $artistsService) {
    $this->config = $config_factory;
    $this->spotify = $spotify;
    $this->artistsService = $artistsService;

  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection
   */
  public static function create(ContainerInterface $container): SpotifyArtistsController|static {
    return new static(
      $container->get('config.factory'),
      $container->get('spotify.api'),
      $container->get('spotify.artists'),
    );
  }

  /**
   * Build single artist page.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function buildPage($artist_id): array {
    $this->token = $this->spotify->spotifyApiToken();
    // Get artists from service.
    $artist = [];
    $spotify_artists = $this->artistsService;
    if ($this->token->status == 200) {
      $artist = $spotify_artists->getArtists($this->token->value, [$artist_id]);
      if ($artist->status === 200) {
        $artist = current($artist->artists);
      }
    }

    return [
      '#theme' => 'spotify_artists_page',
      '#artist' => $artist,
      '#artist_from_url' => $artist_id,
    ];
  }

}
