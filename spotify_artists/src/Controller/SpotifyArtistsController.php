<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\spotify_artists\Service\ArtistsService;
use Drupal\spotify_artists\Service\TopTracksService;
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
   * @param \Drupal\spotify_artists\Service\TopTracksService $topTracksService
   *   Top tracks service.
   */
  public function __construct(protected ArtistsService $artistsService, protected TopTracksService $topTracksService) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SpotifyArtistsController|static {
    return new static(
      $container->get('spotify.artists'),
      $container->get('spotify.top.tracks'),
    );
  }

  /**
   * Build single artist page.
   */
  public function buildPage($artist_id): array {
    // Get artists from config.
    $artistsInConfig = $this->config('spotify_artists.artists')->get('ids');
    $artists = NULL;
    $artist = NULL;
    $tracks = NULL;
    // If id from url is one of the artists in config.
    if ($artistsInConfig && in_array($artist_id, $artistsInConfig)) {
      // Get artists from service.
      $spotify_artist = $this->artistsService;
      $artist = $spotify_artist->getArtists($artist_id);
      if ($artist['status'] === 200) {
        $artist = $artist['artists'][0];
      }
      $topTracks = $this->topTracksService->getTopTracks($artist_id);
      if ($topTracks['status'] === 200) {
        $tracks = $topTracks['tracks'];
      }
    }

    // Else return an empty artist and existing artists.
    else {
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
      '#top_tracks' => $tracks,
    ];
  }

}
