<?php

namespace Drupal\spotify_artists\Event;

/**
 * Defines events for the spotify_artists module.
 */
final class APIEvents {

  /**
   * Name of the event fired.
   *
   * @Event
   *
   * @see \Drupal\spotify_artists\Event\APIReportEvent
   *
   * @var string
   */
  const NEW_REPORT = 'spotify_artists.new_api_report';

}
