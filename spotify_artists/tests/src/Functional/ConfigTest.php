<?php

namespace Drupal\Tests\spotify_artists\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of My Module.
 *
 * @group spotify_artists
 */
class ConfigTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'spotify_artists', 'node'];

  /**
   * Theme to enable. This field is mandatory.
   *
   * @var string
   */
  protected $defaultTheme = 'olivero';

  /**
   * The simplest assert possible.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ExpectationException
   */

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp() : void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
    $this->drupalCreateContentType(['type' => 'article']);
  }

  /**
   * Tests API config form.
   */
  public function testApiForm(): void {
    $this->drupalGet('admin/config/content/spotify_api');
    $this->assertSession()->statusCodeEquals(200);
    // Test submission with environment values.
    $edit = [
      'client_id' => getenv('SPOTIFY_CLIENT_ID'),
      'client_secret' => getenv('SPOTIFY_CLIENT_SECRET'),
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->pageTextContains('Settings saved');
  }

  /**
   * Tests Artists config form.
   */
  public function testArtistsConfig() {
    $this->testAPIForm();
    $this->drupalGet('admin/config/content/spotify_artists');
    // Search for an artist.
    $this->submitForm(['artist' => 'Muse'], 'Search');
    $this->assertSession()->pageTextContains('Now choose from these results');
    // Show error when empty.
    $this->submitForm(['artist' => ''], 'Search');
    $this->assertSession()->pageTextContains('Enter an artist name please!');
    // Clear search.
    $this->submitForm(['artist' => 'Muse'], 'Clear search');
    $this->assertSession()->buttonNotExists('Clear search');
    // Delete an artist.
    $this->submitForm(['table[0]' => '1'], 'Delete selected items');
    $this->assertSession()->pageTextContains('Ids deleted');
  }

  /**
   * Test add artist.
   */
  public function testAddArtist() {
    $this->testAPIForm();
    $this->drupalGet('admin/config/content/spotify_artists');
    // Search for an artist.
    $this->submitForm(['artist' => 'Muse'], 'Search');
    $this->assertSession()->pageTextContains('Now choose from these results');
    $this->submitForm([], 'Add selection to list');
    $this->assertSession()->pageTextContains('Id added');
  }

}
