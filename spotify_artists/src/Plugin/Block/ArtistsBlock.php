<?php

namespace Drupal\spotify_artists\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\spotify_artists\ArtistsService;
use Drupal\spotify_artists\SpotifyApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Spotify artists' Block.
 *
 * @Block(
 *   id = "spotify_artists_block",
 *   admin_label = @Translation("Artists block"),
 *   category = @Translation("Custom"),
 * )
 */
class ArtistsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Artists service.
   *
   * @var \Drupal\spotify_artists\ArtistsService
   */

  public ArtistsService $artistsService;

  /**
   * API service.
   *
   * @var \Drupal\spotify_artists\SpotifyApiService
   */
  public SpotifyApiService $spotify;

  /**
   * Plugin config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                               $plugin_id,
                               $plugin_definition,
                               SpotifyApiService $spotify,
                               ArtistsService $artistsService,
                               ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->spotify = $spotify;
    $this->artistsService = $artistsService;
    $this->config = $config_factory;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ArtistsBlock|static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('spotify.api'),
      $container->get('spotify.artists'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build(): array {
    // Connect to Spotify's API to get token.
    $token = $this->spotify->spotifyApiToken();
    $artists = [];
    // Get ids from config.
    $artists_ids = $this->config->get('spotify_artists.artists')->get('ids');
    // Get artists data from service.
    if ($token->status == 200 && !empty($artists_ids)) {
      $artists = $this->artistsService->getArtists($token->value, $artists_ids);
      if ($artists->status === 200) {
        $artists = $artists->artists;
      }
    }

    return [
      '#theme' => 'spotify_artists_block',
      '#artists' => $artists,
    ];
  }

}
