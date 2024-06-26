<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Users contact settings migration.
 *
 * @group migrate_drupal_6
 */
class MigrateUserContactSettingsTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateUsers(FALSE);
    $this->installSchema('user', ['users_data']);
    $this->executeMigration('d6_user_contact_settings');
  }

  /**
   * Tests the Drupal6 user contact settings migration.
   */
  public function testUserContactSettings() {
    $user_data = \Drupal::service('user.data');
    $module = $key = 'contact';
    $uid = 2;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertSame('1', $setting);

    $uid = 8;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertSame('0', $setting);

    $uid = 15;
    $setting = $user_data->get($module, $uid, $key);
    $this->assertNull($setting);
  }

}
