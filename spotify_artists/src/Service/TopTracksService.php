<?php

namespace Drupal\spotify_artists\Service;

/**
 * Service for top tracks.
 */
class TopTracksService {

  use SpotifyApiTrait;

  /**
   * Function to get top tracks for an artist.
   */
  public function getTopTracks(string $artistId): array {
    return $this->request(
      "/v1/artists/$artistId/top-tracks",
      ['id' => $artistId, 'market' => 'GB'],
      'tracks',
      'tracks'
    );
  }

}
