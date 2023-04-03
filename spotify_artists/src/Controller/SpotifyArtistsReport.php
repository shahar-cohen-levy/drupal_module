<?php

namespace Drupal\spotify_artists\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\spotify_artists\Service\SpotifyArtistsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A report page for API events.
 */
class SpotifyArtistsReport extends ControllerBase {

  /**
   * Constructor to inject dependencies.
   */
  public function __construct(private readonly DateFormatter $dateFormatter,
                              private readonly SpotifyArtistsRepository $repository,
                              private readonly RequestStack $request,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SpotifyArtistsReport|static {
    return new static(
      $container->get('date.formatter'),
      $container->get('spotify.repository'),
      $container->get('request_stack')
    );
  }

  /**
   * Query data from database and display it in a table for each date.
   */
  public function reportPage(): array {
    $error_type_selected = $this->request->getCurrentRequest()->get('type');
    $data = $this->repository->queryAllReports($error_type_selected);
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

    $filters = [];
    foreach ($unique_error_types as $type) {
      if ($error_type_selected !== $type) {
        $filters[$type] = [
          '#title' => $type,
          '#url' => Url::fromRoute('spotify_artists.report', ['type' => $type])->toString(),
        ];
      }
    }
    $build['filters'] = [
      '#theme' => 'spotify_reports_filters',
      '#reset_url' => Url::fromRoute('spotify_artists.report')->toString(),
      '#filters' => $filters,
      '#active_filter' => $error_type_selected,
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
