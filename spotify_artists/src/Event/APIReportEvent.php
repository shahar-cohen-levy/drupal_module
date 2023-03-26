<?php

namespace Drupal\spotify_artists\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Wraps an API report event for event subscribers.
 */
class APIReportEvent extends Event {

  /**
   * API interaction type.
   *
   * @var string
   */
  protected string $apiType;

  /**
   * Constructs an API report event object.
   *
   * @param string $apiType
   *   The API request type.
   */
  public function __construct(string $apiType) {
    $this->apiType = $apiType;
  }

  /**
   * Get the API interaction type.
   *
   * @return string
   *   The type of report.
   */
  public function getApiType(): string {
    return $this->apiType;
  }

}
