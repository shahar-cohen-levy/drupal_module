<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\spotify_artists\Service\ArtistsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Spotify artists module.
 */
class SpotifyArtistsController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\spotify_artists\Service\ArtistsService $artistsService
   *   Artist service.
   */
  public function __construct(protected ArtistsService $artistsService) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SpotifyArtistsController|static {
    return new static(
      $container->get('spotify.artists'),
    );
  }

  /**
   * Build single artist page.
   */
  public function buildPage($artist_id): array {
    // Get artists from config.
    $artistsInConfig = $this->config('spotify_artists.artists')->get('ids');
    $artists = Null;
    // If id from url is one of the artists in config.
    if ($artistsInConfig && in_array($artist_id, $artistsInConfig)) {
      // Get artists from service.
      $spotify_artists = $this->artistsService;
      $artist = $spotify_artists->getArtists($artist_id);
      if ($artist['status'] === 200) {
        $artist = $artist['artists'][array_search($artist_id, $artistsInConfig)];
      }
    }

    // Else return an empty artist and existing artists.
    else {
      $artist = NULL;
      // Get artists data from service.
      $artists = $this->artistsService->getArtists();
      if ($artists['status'] === 200) {
        $artists = $artists['artists'];
      }

    }

    return [
      '#theme' => 'spotify_artists_page',
      '#artist' => $artist,
      '#existing_artists' => $artists,
    ];
  }

}
