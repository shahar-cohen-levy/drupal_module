<?php

namespace Drupal\spotify_artists\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\spotify_artists\Service\ArtistsService;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration,
                               $plugin_id,
                               $plugin_definition,
                               public ArtistsService $artistsService,
                              ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): ArtistsBlock|static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('spotify.artists'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    // Get artists data from service.
    $artists = $this->artistsService->getArtists();
    if ($artists['status'] === 200) {
      $artists = $artists['artists'];
    }

    return [
      '#theme' => 'spotify_artists_block',
      '#artists' => $artists,
    ];
  }

}
