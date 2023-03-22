<?php

namespace Drupal\spotify_artists;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;

/**
 * Search Artists service.
 */
class SearchArtistsService {
  /**
   * API token.
   *
   * @var string
   */
  protected string $token;

  /**
   * Constructor.
   *
   * @param \Drupal\spotify_artists\SpotifyApiService $spotifyApiService
   *   spotify API service.
   */
  public function __construct(protected SpotifyApiService $spotifyApiService) {
    $this->token = $this->spotifyApiService->spotifyApiToken()->value;
  }

  /**
   * Search Artists.
   *
   * @return object
   *   array of artists.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   Error message.
   */
  public function searchArtists($query): object {

    $client = new Client([
      'base_uri' => 'https://api.spotify.com',
    ]);
    try {
      $request = $client->request('GET', '/v1/search?',
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json',
          ],
          'query' => [
            'q' => $query,
            'type' => 'artist',
          ],
        ],
      );
      $body = json_decode($request->getBody());
      $status = json_decode($request->getStatusCode());
      return (object) ["status" => $status, "response" => $body->artists];
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return (object) ["status" => $status];
    }

  }

}
