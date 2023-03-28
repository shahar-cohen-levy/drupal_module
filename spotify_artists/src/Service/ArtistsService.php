<?php

namespace Drupal\spotify_artists\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service to get artists.
 */
class ArtistsService {

  /**
   * API token.
   *
   * @var string|null
   */
  protected string|null $token;

  private $tempStore;

  /**
   * Constructor.
   *
   * @param \Drupal\spotify_artists\Service\SpotifyApiService $spotifyApiService
   *   Spotify API service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
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
      // Dispatch event for reports.
      $event = new APIReportEvent('artists_request');
      $this->dispatcher->dispatch($event, APIEvents::NEW_REPORT);
      // Save response to store.
      $this->tempStore->set(
        'artists_array',
         ["status" => $status, "artists" => $body->artists]);
      // Return response.
      return ["status" => $status, "artists" => $body->artists];

    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return ["status" => $status];

    }
    catch (GuzzleException $e) {
      return $e->getCode();
    }
    catch (TempStoreException $e) {
      return $e->getCode();
    }

  }

}
