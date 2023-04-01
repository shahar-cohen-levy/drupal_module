<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A report page for API events.
 */
class SpotifyArtistsReport extends ControllerBase {

  /**
   * Constructor to inject dependencies.
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
   * Query data from database and display it in a table for each date.
   */
  public function reportPage(): array {
    $data = NULL;
    try {
      $data = $this->connection->query('SELECT date_time,type FROM spotify_artists_reports ORDER BY id DESC')->fetchAll();
    }
    catch (\Exception $e) {
      $this->getLogger('spotify.artists')->info($e->getMessage());
    }

    $events = [];
    if ($data) {
      foreach ($data as $event) {
        // Formatting date and time.
        $date = $this->dateFormatter->format($event->date_time, 'html_date');
        $time = $this->dateFormatter->format($event->date_time, 'html_time');
        // Formatting type.
        $type = ucfirst(str_replace("_", " ", $event->type));
        $events[] = ['date' => $date, 'time' => $time, 'type' => $type];
      }
    }
    // Group events by date.
    $groupByDate = [];
    foreach ($events as $group) {
      $groupByDate[$group['date']][] = [$group['time'], $group['type']];
    }
    // Create a table for each date.
    $build = [];
    $header = [
      'date' => t('Time'),
      'Type' => t('Type'),
    ];
    foreach ($groupByDate as $index => $eventsInDate) {
      $build[$index] = [
        '#type' => 'details',
        '#title' => $this->t('Reports for @date', ['@date' => $index]),
      ];
      $build[$index][$index] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $eventsInDate,

      ];
    }
    return $build;
  }

}
