<?php

namespace Drupal\spotify_artists\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use Drupal\spotify_artists\Service\SpotifyArtistsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to APIEvents::NEW_REPORT events and react to new reports.
 */
class APISubscriber implements EventSubscriberInterface {

  /**
   * Inject Account Proxy service.
   */
  public function __construct(private readonly SpotifyArtistsRepository $repository,
                              private readonly TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[APIEvents::NEW_REPORT][] = ['save'];
    return $events;
  }

  /**
   * If this the types are as specified notify Me.
   *
   * @param \Drupal\spotify_artists\Event\APIReportEvent $event
   *   The event object containing types.
   */
  public function save(APIReportEvent $event) {
    // Get current time.
    $dateTime = $this->time->getCurrentTime();
    // Save to database.
    $entry = [
      'date_time' => $dateTime,
      'type' => $event->getApiType(),
    ];
    $this->repository->insert($entry);
  }

}
