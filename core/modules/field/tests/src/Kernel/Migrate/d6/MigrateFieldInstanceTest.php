<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel\Migrate\d6;

use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Migrate field instances.
 *
 * @group migrate_drupal_6
 */
class MigrateFieldInstanceTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'menu_ui', 'node'];

  /**
   * Tests migration of file variables to file.settings.yml.
   */
  public function testFieldInstanceMigration() {
    $this->migrateFields();
    $this->installConfig(['comment']);
    $this->executeMigration('d6_comment_type');

    $entity = Node::create(['type' => 'story']);
    // Test a text field.
    /** @var \Drupal\field\FieldConfigInterface $field */
    $field = FieldConfig::load('node.story.field_test');
    $this->assertSame('Text Field', $field->label());
    // field_test is a text_long field, which have no settings.
    $this->assertSame(['allowed_formats' => []], $field->getSettings());
    $this->assertSame('text for default value', $entity->field_test->value);

    // Test a number field.
    $field = FieldConfig::load('node.story.field_test_two');
    $this->assertSame('Integer Field', $field->label());
    $expected = [
      'min' => 10,
      'max' => 100,
      'prefix' => 'pref',
      'suffix' => 'suf',
      'unsigned' => FALSE,
      'size' => 'normal',
    ];
    $this->assertSame($expected, $field->getSettings());

    $field = FieldConfig::load('node.story.field_test_four');
    $this->assertSame('Float Field', $field->label());
    $expected = [
      'min' => 100.0,
      'max' => 200.0,
      'prefix' => 'id-',
      'suffix' => '',
    ];
    $this->assertSame($expected, $field->getSettings());

    // Test email field.
    $field = FieldConfig::load('node.story.field_test_email');
    $this->assertSame('Email Field', $field->label());
    $this->assertSame('benjy@example.com', $entity->field_test_email->value);

    // Test image field.
    $field = FieldConfig::load('node.story.field_test_imagefield');
    $this->assertSame('Image Field', $field->label());
    $field_settings = $field->getSettings();
    $this->assertSame('', $field_settings['max_resolution']);
    $this->assertSame('', $field_settings['min_resolution']);
    $this->assertSame('', $field_settings['file_directory']);
    $this->assertSame('png gif jpg jpeg', $field_settings['file_extensions']);
    $this->assertSame('public', $field_settings['uri_scheme']);

    // Test a filefield.
    $field = FieldConfig::load('node.story.field_test_filefield');
    $this->assertSame('File Field', $field->label());
    $expected = [
      'file_extensions' => 'txt pdf doc',
      'file_directory' => 'images',
      'description_field' => TRUE,
      'max_filesize' => '200KB',
      'target_type' => 'file',
      'display_field' => FALSE,
      'display_default' => FALSE,
      'uri_scheme' => 'public',
      'handler' => 'default:file',
      'handler_settings' => [],
    ];
    $field_settings = $field->getSettings();
    ksort($expected);
    ksort($field_settings);
    // This is the only way to compare arrays.
    $this->assertSame($expected, $field_settings);

    // Test a link field.
    $field = FieldConfig::load('node.story.field_test_link');
    $this->assertSame('Link Field', $field->label());
    $expected = ['title' => 2, 'link_type' => LinkItemInterface::LINK_GENERIC];
    $this->assertSame($expected, $field->getSettings());
    $this->assertSame('default link title', $entity->field_test_link->title, 'Field field_test_link default title is correct.');
    $this->assertSame('https://www.drupal.org', $entity->field_test_link->uri);
    $this->assertSame([], $entity->field_test_link->options['attributes']);

    // Test date field.
    $field = FieldConfig::load('node.story.field_test_date');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('Date Field', $field->label());
    $this->assertSame('An example date field.', $field->getDescription());
    $expected = ['datetime_type' => 'datetime'];
    $this->assertSame($expected, $field->getSettings());
    $expected = [
      [
        'default_date_type' => 'relative',
        'default_date' => 'blank',
      ],
    ];
    $this->assertSame($expected, $field->getDefaultValueLiteral());
    $this->assertTrue($field->isTranslatable());

    // Test datetime field.
    $field = FieldConfig::load('node.story.field_test_datetime');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('Datetime Field', $field->label());
    $this->assertSame('An example datetime field.', $field->getDescription());
    $expected = ['datetime_type' => 'datetime'];
    $this->assertSame($expected, $field->getSettings());
    $expected = [];
    $this->assertSame($expected, $field->getDefaultValueLiteral());
    $this->assertTrue($field->isTranslatable());

    // Test datestamp field.
    $field = FieldConfig::load('node.story.field_test_datestamp');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('Date Stamp Field', $field->label());
    $this->assertSame('An example date stamp field.', $field->getDescription());
    $expected = [];
    $this->assertSame($expected, $field->getSettings());
    $expected = [];
    $this->assertSame($expected, $field->getDefaultValueLiteral());
    $this->assertTrue($field->isTranslatable());

    // Test a node reference field, migrated to entity reference.
    $field = FieldConfig::load('node.employee.field_company');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('entity_reference', $field->getType());
    $this->assertSame('Company', $field->label());
    $this->assertSame('default:node', $field->getSetting('handler'));
    $this->assertSame([], $field->getSetting('handler_settings'));
    $this->assertSame('node', $field->getSetting('target_type'));
    $this->assertSame([], $field->getDefaultValueLiteral());
    $this->assertTrue($field->isTranslatable());

    // Test a user reference field, migrated to entity reference.
    $field = FieldConfig::load('node.employee.field_commander');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertSame('entity_reference', $field->getType());
    $this->assertSame('Commanding Officer', $field->label());
    $this->assertSame('default:user', $field->getSetting('handler'));
    $this->assertSame([], $field->getSetting('handler_settings'));
    $this->assertSame('user', $field->getSetting('target_type'));
    $this->assertSame([], $field->getDefaultValueLiteral());
    $this->assertTrue($field->isTranslatable());

    // Test a synchronized field is not translatable.
    $field = FieldConfig::load('node.employee.field_sync');
    $this->assertInstanceOf(FieldConfig::class, $field);
    $this->assertFalse($field->isTranslatable());

    // Test a comment with a long name.
    $field = FieldConfig::load('comment.comment_node_a_thirty_two_char.comment_body');
    $this->assertInstanceOf(FieldConfig::class, $field);
  }

  /**
   * Tests migrating fields into non-existent content types.
   */
  public function testMigrateFieldIntoUnknownNodeType() {
    $this->sourceDatabase->delete('node_type')
      ->condition('type', 'test_planet')
      ->execute();
    // The field migrations use the migration plugin to ensure that the node
    // types exist, so this should produce no failures...
    $this->migrateFields();

    // ...and the field instances should not have been migrated.
    $this->assertNull(FieldConfig::load('node.test_planet.field_multivalue'));
    $this->assertNull(FieldConfig::load('node.test_planet.field_test_text_single_checkbox'));
  }

}
