<?php

namespace Drupal\spotify_artists\Service;

use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * @param \Drupal\spotify_artists\Service\SpotifyApiService $spotifyApiService
   *   spotify API service.
   */
  public function __construct(protected SpotifyApiService $spotifyApiService, protected EventDispatcherInterface $dispatcher) {
    $this->token = $this->spotifyApiService->spotifyApiToken()['status'] == 200 ? $this->spotifyApiService->spotifyApiToken()['value'] : '';
  }

  /**
   * Search Artists.
   *
   * @param $query
   *
   * @return array array of artists.
   *   array of artists.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException Error message.
   */
  public function searchArtists($query): array {

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
      // Dispatch event for reports.
      $event = new APIReportEvent('search_query');
      $this->dispatcher->dispatch($event, APIEvents::NEW_REPORT);

      return ["status" => $status, "response" => $body->artists];
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return ["status" => $status];
    }

  }

}
