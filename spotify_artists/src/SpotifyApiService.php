<?php

namespace Drupal\spotify_artists;

use Psr\Log\LoggerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TempStore\TempStoreException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * API service.
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
    protected PrivateTempStoreFactory $temp_store_factory
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
  public function accessWithCodeAuthorization(): object {
    $client = new Client();

    try {
      $res = $client->post('https://accounts.spotify.com/api/token', [

        'headers' => [
          'Content-Type' => 'application/x-www-form-urlencoded',
          'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
        ],

        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);

      $body = json_decode($res->getBody());
      $status = json_decode($res->getStatusCode());
      $this->saveTokenToSession((object) [
        "status" => $status,
        "value" => $body->{"access_token"},
      ]);
      return (object) ["status" => $status, "value" => $body->{"access_token"}];
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
      $status = json_decode($response->getStatusCode());
      return (object) ["status" => $status];
    }
    catch (GuzzleException $e) {
      $response = $e->getMessage();
      $this->logger->error($response);
      return (object) ["status" => 999];
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
  public function spotifyApiToken(): string|object|null {
    if ($this->privateTempStore->get('token_s') !== NULL) {
      return $this->privateTempStore->get('token_s');
    }
    else {
      return $this->accessWithCodeAuthorization();
    }
  }

}
