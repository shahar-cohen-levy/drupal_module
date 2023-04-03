<?php

namespace Drupal\spotify_artists\Service;

/**
 * Service to get artists.
 */
class ArtistsService {

  use SpotifyApiTrait;

  /**
   * Function to get artists.
   */
  public function getArtists(array|string $artistsIds = NULL): array {
    // If artists ids is not passed to service get it from config.
    if (!isset($artistsIds)) {
      $artistsIds = $this->configFactory->get('spotify_artists.artists')->get('ids');
    }
    // If $artistsIds is an array return a string with commas,
    // if not return string.
    $artists_list = is_array($artistsIds) ? implode(",", $artistsIds) : $artistsIds;
    // Check if there's a valid object in store.
    if ($this->tempStore->get('artists_array') !== NULL && $this->tempStore->get('artists_array')['status'] === 200) {
      $artistsFromStore = $this->tempStore->get('artists_array')['artists'];
      $idsFromStore = [];
      // Create an array of ids from store for comparison.
      foreach ($artistsFromStore as $artist) {
        $idsFromStore[] = $artist->id;
      }
      // If the ids are the same or if artists id is a string
      // (coming from SpotifyArtistsController) return the object from store.
      if ($idsFromStore == $artistsIds || is_string($artistsIds)) {
        return $this->tempStore->get('artists_array');
      }
    }
    // Else make a request from the API.
    return $this->request(
      '/v1/artists/',
      ['ids' => $artists_list],
      'artists',
      'Artists');
  }

}
