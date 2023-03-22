<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\spotify_artists\ArtistsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Spotify artists module.
 */
class SpotifyArtistsController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\spotify_artists\ArtistsService $artistsService
   *   Artist service.
   */
  public function __construct(protected ArtistsService $artistsService) {
  }

  /**
   * {@inheritdoc}
   *
   * @noinspection PhpParamsInspection
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
    // Get artists from service.
    $spotify_artists = $this->artistsService;
    $artist = $spotify_artists->getArtists([$artist_id]);
    if ($artist->status === 200) {
      $artist = current($artist->artists);
    }

    return [
      '#theme' => 'spotify_artists_page',
      '#artist' => $artist,
      '#artist_from_url' => $artist_id,
    ];
  }

}
