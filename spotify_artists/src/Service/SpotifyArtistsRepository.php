<?php

namespace Drupal\spotify_artists\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Repository for database queries for this module.
 */
class SpotifyArtistsRepository {
  use MessengerTrait;
  use StringTranslationTrait;
  use MessengerTrait;
  use LoggerChannelTrait;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The MailManagerInterface service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The ConfigFactoryInterface service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The LanguageManagerInterface service.
   */
  public function __construct(private readonly Connection $connection,
                              TranslationInterface $translation,
                              MessengerInterface $messenger,
                              private readonly MailManagerInterface $mailManager,
                              private readonly ConfigFactoryInterface $configFactory,
                              private readonly LanguageManagerInterface $languageManager
  ) {
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Insert data to 'spotify_artists_reports' table.
   */
  public function insert(array $entry): int|string|null {
    try {
      $return_value = $this->connection->insert('spotify_artists_reports')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('spotify.artists')->info($e->getMessage());
      $this->messenger->addError($this->t('Cannot write to report, spotify_artists_reports table does not exist, try reinstalling the module'));
      $this->mailManager->mail(
        'spotify_artists',
        'reports_message',
        $this->configFactory->get('system.site')->get('mail'),
        $this->languageManager->getDefaultLanguage()->getId(),
        $e->getMessage()
      );
    }
    return $return_value ?? NULL;
  }

  /**
   * A custom function to query all reports and order them.
   */
  public function queryAllReports():array|null {

    try {
      return $this->connection
        ->select('spotify_artists_reports')
        ->fields('spotify_artists_reports', ['date_time', 'type'])
        ->orderBy('date_time', 'DESC')
        ->execute()
        ->fetchAll();
    }
    catch (\Exception $e) {
      $this->getLogger('spotify.artists')->info($e->getMessage());
      return NULL;
    }
  }

}
