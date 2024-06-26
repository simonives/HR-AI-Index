<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the migrate.stub Service.
 *
 * @group migrate
 */
class MigrateStubTest extends MigrateTestBase {
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'user',
    'text',
    'filter',
    'migrate_stub_test',
  ];

  /**
   * The migrate stub service.
   *
   * @var \Drupal\migrate\MigrateStubInterface
   */
  protected $migrateStub;

  /**
   * The migration lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setTestLogger();
    $this->migrateStub = $this->container->get('migrate.stub');
    $this->migrateLookup = $this->container->get('migrate.lookup');
    $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installConfig(['node', 'user']);
    $this->createContentType(['type' => 'node_lookup']);
  }

  /**
   * Tests stub creation.
   */
  public function testCreateStub() {
    $this->assertSame([], $this->migrateLookup->lookup('sample_stubbing_migration', [17]));
    $ids = $this->migrateStub->createStub('sample_stubbing_migration', [17]);
    $this->assertSame([$ids], $this->migrateLookup->lookup('sample_stubbing_migration', [17]));
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($ids['nid']));
  }

  /**
   * Tests raw stub creation.
   */
  public function testCreateStubRawReturn() {
    $this->assertSame([], $this->migrateLookup->lookup('sample_stubbing_migration', [17]));
    $ids = $this->migrateStub->createStub('sample_stubbing_migration', [17], [], FALSE);
    $this->assertSame($ids, [$this->migrateLookup->lookup('sample_stubbing_migration', [17])[0]['nid']]);
    $this->assertNotNull(\Drupal::entityTypeManager()->getStorage('node')->load($ids[0]));
  }

  /**
   * Tests stub creation with default values.
   */
  public function testStubWithDefaultValues() {
    $this->assertSame([], $this->migrateLookup->lookup('sample_stubbing_migration', [17]));
    $ids = $this->migrateStub->createStub('sample_stubbing_migration', [17], ['title' => "Placeholder for source id 17"]);
    $this->assertSame([$ids], $this->migrateLookup->lookup('sample_stubbing_migration', [17]));
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($ids['nid']);
    $this->assertNotNull($node);
    // Test that our default value was set as the node title.
    $this->assertSame("Placeholder for source id 17", $node->label());

    // Test that running the migration replaces the node title.
    $this->executeMigration('sample_stubbing_migration');
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($ids['nid']);
    $this->assertSame("Sample 1", $node->label());
  }

  /**
   * Tests stub creation with bundle fields.
   */
  public function testStubWithBundleFields() {
    $this->createContentType(['type' => 'node_stub']);
    // Make "Body" field required to make stubbing populate field value.
    $body_field = FieldConfig::loadByName('node', 'node_stub', 'body');
    $body_field->setRequired(TRUE)->save();

    $this->assertSame([], $this->migrateLookup->lookup('sample_stubbing_migration', [33]));
    $ids = $this->migrateStub->createStub('sample_stubbing_migration', [33], []);
    $this->assertSame([$ids], $this->migrateLookup->lookup('sample_stubbing_migration', [33]));
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($ids['nid']);
    $this->assertNotNull($node);
    // Make sure the "Body" field value was populated.
    $this->assertNotEmpty($node->get('body')->value);
  }

  /**
   * Tests invalid source id count.
   */
  public function testInvalidSourceIdCount() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Expected and provided source id counts do not match.');
    $this->migrateStub->createStub('sample_stubbing_migration_with_multiple_source_ids', [17]);
  }

  /**
   * Tests invalid source ids keys.
   */
  public function testInvalidSourceIdKeys() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("'version_id' is defined as a source ID but has no value.");
    $this->migrateStub->createStub('sample_stubbing_migration_with_multiple_source_ids', ['id' => 17, 'not_a_key' => 17]);
  }

}
