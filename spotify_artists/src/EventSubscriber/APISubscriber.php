<?php

namespace Drupal\spotify_artists\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\spotify_artists\Event\APIEvents;
use Drupal\spotify_artists\Event\APIReportEvent;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to APIEvents::NEW_REPORT events and react to new reports.
 */
class APISubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;
  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Inject Account Proxy service.
   */
  public function __construct(private readonly AccountProxyInterface $accountProxy,
                              private readonly Connection $connection,
                              private readonly TimeInterface $time
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[APIEvents::NEW_REPORT][] = ['notifyAndLog'];
    return $events;
  }

  /**
   * If this the types are as specified notify Me.
   *
   * @param \Drupal\spotify_artists\Event\APIReportEvent $event
   *   The event object containing types.
   */
  public function notifyAndLog(APIReportEvent $event) {
    if ($this->accountProxy->hasPermission('Access module configuration')) {
      switch ($event->getApiType()) {
        case 'artists_request':
          $this->messenger()
            ->addStatus($this->t('Artists API request has been made. This message was set by an event subscriber. See @method()', ['@method' => __METHOD__]));
          break;

        case 'token':
          $this->messenger()
            ->addStatus($this->t('API token has been used. This message was set by an event subscriber. See @method()', ['@method' => __METHOD__]));
          break;

        case 'search_query':
          $this->messenger()
            ->addStatus($this->t('Search has been made. This message was set by an event subscriber. See @method()', ['@method' => __METHOD__]));
          break;

        default:
          return;
      }
    }
    // Get current time.
    $dateTime = $this->time->getCurrentTime();
    // Save to database.
    $entry = [
      'date_time' => $dateTime,
      'type' => $event->getApiType(),
    ];
    try {
      $this->connection->insert('spotify_artists_reports')->fields($entry)->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('spotify.artists')->info($e->getMessage());
    }
  }

}
