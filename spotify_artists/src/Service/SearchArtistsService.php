<?php

namespace Drupal\spotify_artists\Service;

/**
 * Search Artists service.
 */
class SearchArtistsService {

  use SpotifyApiTrait;

  /**
   * Search Artists.
   *
   * @param string $query
   *   Query from search input.
   *
   * @return array
   *   array of artists.
   */
  public function searchArtists(string $query): array {

    return $this->request(
      '/v1/search?',
      ['q' => $query, 'type' => 'artist'],
      'artists'
    );

  }

}
