<?php

namespace Drupal\spotify_artists;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * Service to get artists.
 */
class ArtistsService {

  /**
   * API token.
   *
   * @var string
   */
  protected string $token;

  /**
   * Function to get artists.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getArtists($token, $artistsIdsArray = []): object {

    $artists_list = implode(",", $artistsIdsArray);
    try {
      $client = new Client([
        'base_uri' => 'https://api.spotify.com',
      ]);

      $request = $client->request('GET', '/v1/artists/',
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
          ],
          'query'   => [
            'ids' => $artists_list,
          ],
        ],
      );
      $body    = json_decode($request->getBody());
      $status  = json_decode($request->getStatusCode());
      return (object) ["status" => $status, "artists" => $body->artists];

    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return (object) ["status" => $status];

    }

  }

}
