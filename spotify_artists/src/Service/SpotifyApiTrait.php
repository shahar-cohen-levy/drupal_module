<?php

namespace Drupal\spotify_artists\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A trait to create API services with spotify.
 */
trait SpotifyApiTrait {
  /**
   * API token.
   *
   * @var string|null
   */
  protected string|null $token;
  /**
   * PrivateTempStore.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  private PrivateTempStore $tempStore;

  /**
   * Constructor.
   *
   * @param \Drupal\spotify_artists\Service\SpotifyApiService $spotifyApiService
   *   Spotify API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Temp store.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   Event dispatcher.
   */
  public function __construct(
    protected SpotifyApiService $spotifyApiService,
    protected ConfigFactoryInterface $configFactory,
    protected PrivateTempStoreFactory $temp_store_factory,
    protected EventDispatcherInterface $dispatcher,
  ) {
    $this->token = $this->spotifyApiService->spotifyApiToken()['status'] == 200 ? $this->spotifyApiService->spotifyApiToken()['value'] : '';
    $this->tempStore = $this->temp_store_factory->get('spotify_artists');
  }

  /**
   * You need to pass 3 parameters.
   *
   * 1. Uri: the part after base_uri. i.e: '/artists'
   * 2. Query: array with values. i.e: ['ids' => $artists_list]
   * 3. Response name. i.e: 'artists'
   *
   * @see https://developer.spotify.com/documentation/web-api/reference/get-multiple-artists
   *
   * @return array
   *   returns array with artist data.
   */
  private function request(string $uri, array $query, string $responseName, string $reportName) :array|int {
    try {
      $client = new Client([
        'base_uri' => 'https://api.spotify.com',
      ]);

      $request = $client->request('GET', $uri,
        [
          'headers' => [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type'  => 'application/json',
          ],
          'query'   => $query,
        ],
      );
      $body    = json_decode($request->getBody());
      $status  = json_decode($request->getStatusCode());
      // Dispatch event for reports.
      $event = new APIReportEvent($reportName);
      $this->dispatcher->dispatch($event, APIEvents::NEW_REPORT);
      // Save response to store.
      $this->tempStore->set(
        $responseName,
        ["status" => $status, $responseName => $body->$responseName]);
      // Return response.
      return ["status" => $status, $responseName => $body->$responseName];

    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return ["status" => $status];

    }
    catch (GuzzleException $e) {
      return ["status" => $e->getCode()];
    }
    catch (TempStoreException $e) {
      return ["status" => $e->getCode()];
    }
  }

}
