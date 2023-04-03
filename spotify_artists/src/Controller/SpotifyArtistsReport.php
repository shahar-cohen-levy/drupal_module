<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\spotify_artists\Service\SpotifyArtistsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A report page for API events.
 */
class SpotifyArtistsReport extends ControllerBase {

  /**
   * Constructor to inject dependencies.
   */
  public function __construct(private readonly DateFormatter $dateFormatter,
                              private readonly SpotifyArtistsRepository $repository,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SpotifyArtistsReport|static {
    return new static(
      $container->get('date.formatter'),
      $container->get('spotify.repository'),
    );
  }

  /**
   * Query data from database and display it in a table for each date.
   */
  public function reportPage($error_type): array {
    $data = $this->repository->queryAllReports($error_type);
    $events = [];
    $error_types = [];
    if ($data) {
      foreach ($data as $event) {
        // Formatting date and time.
        $date = $this->dateFormatter->format($event->date_time, 'html_date');
        $time = $this->dateFormatter->format($event->date_time, 'html_time');
        $type = $event->type;
        $events[] = ['date' => $date, 'time' => $time, 'type' => $type];
        $error_types[] = $type;
      }
    }

    $unique_error_types = array_unique($error_types, SORT_REGULAR);
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

    $build['filters'] = [
      '#theme' => 'spotify_reports_filters',
      '#filters' => $unique_error_types,
      '#active_filter' => $error_type,
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
