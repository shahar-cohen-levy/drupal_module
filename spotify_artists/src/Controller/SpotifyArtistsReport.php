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
      $container->get('spotify.repository')
    );
  }

  /**
   * Query data from database and display it in a table for each date.
   */
  public function reportPage(): array {
    $data = $this->repository->queryAllReports();
    $events = [];
    if ($data) {
      foreach ($data as $event) {
        // Formatting date and time.
        $date = $this->dateFormatter->format($event->date_time, 'html_date');
        $time = $this->dateFormatter->format($event->date_time, 'html_time');
        $type = ucfirst(
          preg_replace(
            ['/\/v1\/|\/api\/|artists\/[A-Za-z0-9]+/',
              '/[\/?]/',
              '/-/',
            ],
            ['', '', ' '],
            $event->type));
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
