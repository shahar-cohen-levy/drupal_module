<?php

namespace Drupal\spotify_artists\Service;

use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use Psr\Log\LoggerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\TempStoreException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Logger\LoggerChannelTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * API service used to get a token used for requests. Client/secret sources:.
 *
 *  1. Config settings.
 *  2. Environment variables.
 *  3. Passed directly to accessWithCodeAuthorization method.
 */
class SpotifyApiService {
  use LoggerChannelTrait;

  /**
   * Client id.
   *
   * @var string|null
   */
  protected ?string $clientId;
  /**
   * Client secret.
   *
   * @var string|null
   */
  protected ?string $clientSecret;
  /**
   * API token.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $privateTempStore;


  /**
   * Logger Service Object.
   *
   * @var \Psr\Log\LoggerInterface
   */

  protected LoggerInterface $logger;

  /**
   * Constructor to get values from config.
   */
  public function __construct(
    protected ConfigFactoryInterface $config_factory,
    protected PrivateTempStoreFactory $temp_store_factory,
    protected EventDispatcherInterface $event_dispatcher
  ) {
    $this->privateTempStore = $this->temp_store_factory->get('spotify_artists');
    $this->clientId = $this->config_factory->get('spotify_artists.api')->get('client_id') ?: getenv('SPOTIFY_CLIENT_ID');
    $this->clientSecret = $this->config_factory->get('spotify_artists.api')->get('client_secret') ?: getenv('SPOTIFY_CLIENT_SECRET');
    $this->logger = $this->getLogger('spotify.artists');
  }

  /**
   * Save token to session.
   */
  public function saveTokenToSession($token): void {
    try {
      $this->privateTempStore->set('token_s', $token);
    }
    catch (TempStoreException $e) {
      $this->logger->error($e->getMessage());
    }
  }

  /**
   * Generate token.
   */
  public function accessWithCodeAuthorization($id = NULL, $secret = NULL): array {
    $id = $id ?: $this->clientId;
    $secret = $secret ?: $this->clientSecret;
    $client = new Client();

    try {
      $res = $client->post('https://accounts.spotify.com/api/token', [

        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
          'Authorization' => 'Basic ' . base64_encode($id . ':' . $secret),
        ],

        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);

      $body = json_decode($res->getBody());
      $status = json_decode($res->getStatusCode());

      // Dispatch an event for reports section.
      $event = new APIReportEvent('token');
      $this->event_dispatcher->dispatch($event, APIEvents::NEW_REPORT);

      $this->saveTokenToSession([
        "status" => $status,
        "value" => $body->{"access_token"},
      ]);
      return ["status" => $status, "value" => $body->{"access_token"}];
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return ["status" => $status];
    }
    catch (GuzzleException $e) {
      $response = $e->getMessage();
      $this->logger->error($response);
      return ["status" => 999];
    }
  }

  /**
   * If token exists in session return it.
   *
   * Otherwise, create a new one and return it.
   *
   * @return string|object|null
   *   A token as a string.
   */
  public function spotifyApiToken(): string|array|null {
    if ($this->privateTempStore->get('token_s') !== NULL) {
      return $this->privateTempStore->get('token_s');
    }
    else {
      return $this->accessWithCodeAuthorization();
    }
  }

}
