<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class SpotifyArtistsReport extends ControllerBase {

  /**
   *
   */
  public function __construct(private readonly Connection $connection, private readonly DateFormatter $dateFormatter) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SpotifyArtistsReport|static {
    return new static(
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   *
   */
  public function reportPage(): array {
    $data = NULL;
    try {
      $data = $this->connection->query('SELECT date_time,type FROM spotify_artists_reports ORDER BY id DESC')->fetchAll();
    }
    catch (\Exception $e) {
      $this->getLogger('spotify.artists')->info($e->getMessage());
    }

    $header = [
      'date' => t('Date & Time'),
      'Type' => t('Type'),
    ];

    $rows = [];
    if ($data) {
      foreach ($data as $row) {
        // Formatting date and time.
        $row->date_time = $this->dateFormatter->format($row->date_time);
        // Formatting type.
        $row->type = ucfirst(str_replace("_", " ", $row->type));
        $rows[] = ['data' => (array) $row];
      }
    }


    return [
      '#caption' => $this->t('Reports for every API request in order to monitor unnecessary requests'),
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

}
