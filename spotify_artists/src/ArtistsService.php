<?php

namespace Drupal\spotify_artists;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

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
   * Constructor.
   *
   * @param \Drupal\spotify_artists\SpotifyApiService $spotifyApiService
   *   Spotify API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(
    protected SpotifyApiService $spotifyApiService,
    protected ConfigFactoryInterface $configFactory) {
    $this->token = $this->spotifyApiService->spotifyApiToken()->value;
  }

  /**
   * Function to get artists.
   */
  public function getArtists(array $artistsIds = NULL): object {
    if (!isset($artistsIds)) {
      $artistsIds = $this->configFactory->get('spotify_artists.artists')->get('ids');
    }
    $artists_list = implode(",", $artistsIds);
    try {
      $client = new Client([
        'base_uri' => 'https://api.spotify.com',
      ]);

      $request = $client->request('GET', '/v1/artists/',
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $this->token,
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
    catch (GuzzleException $e) {
      return $e->getCode();
    }

  }

}
