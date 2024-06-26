<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity\TypedData;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\Core\Entity\ContentEntityBaseMockableClass;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Language\Language;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Plugin\DataType\EntityAdapter
 * @group Entity
 * @group TypedData
 */
class EntityAdapterUnitTest extends UnitTestCase {

  /**
   * The bundle used for testing.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The content entity used for testing.
   */
  protected ContentEntityBaseMockableClass&MockObject $entity;

  /**
   * The content entity adapter under test.
   *
   * @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter
   */
  protected $entityAdapter;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The entity type manager used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The type ID of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The typed data manager used for testing.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedDataManager;

  /**
   * The field item list returned by the typed data manager.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldItemList;

  /**
   * The field type manager used for testing.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldTypePluginManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $languageManager;

  /**
   * The UUID generator used for testing.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The entity ID.
   *
   * @var int
   */
  protected $id;

  /**
   * Field definitions.
   *
   * @var \Drupal\Core\Field\BaseFieldDefinition[]
   */
  protected $fieldDefinitions;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->id = 1;
    $values = [
      'id' => $this->id,
      'uuid' => '3bb9ee60-bea5-4622-b89b-a63319d10b3a',
      'defaultLangcode' => [LanguageInterface::LANGCODE_DEFAULT => 'en'],
    ];
    $this->entityTypeId = $this->randomMachineName();
    $this->bundle = $this->randomMachineName();

    $this->entityType = $this->createMock('\Drupal\Core\Entity\EntityTypeInterface');
    $this->entityType->expects($this->any())
      ->method('getKeys')
      ->willReturn([
        'id' => 'id',
        'uuid' => 'uuid',
      ]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($this->entityType);

    $this->uuid = $this->createMock('\Drupal\Component\Uuid\UuidInterface');

    $this->typedDataManager = $this->createMock(TypedDataManagerInterface::class);
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('entity')
      ->willReturn(['class' => '\Drupal\Core\Entity\Plugin\DataType\EntityAdapter']);
    $this->typedDataManager->expects($this->any())
      ->method('getDefaultConstraints')
      ->willReturn([]);

    $validation_constraint_manager = $this->getMockBuilder('\Drupal\Core\Validation\ConstraintManager')
      ->disableOriginalConstructor()
      ->getMock();
    $validation_constraint_manager->expects($this->any())
      ->method('create')
      ->willReturn([]);
    $this->typedDataManager->expects($this->any())
      ->method('getValidationConstraintManager')
      ->willReturn($validation_constraint_manager);

    $not_specified = new Language(['id' => LanguageInterface::LANGCODE_NOT_SPECIFIED, 'locked' => TRUE]);
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->languageManager->expects($this->any())
      ->method('getLanguages')
      ->willReturn([LanguageInterface::LANGCODE_NOT_SPECIFIED => $not_specified]);

    $this->languageManager->expects($this->any())
      ->method('getLanguage')
      ->with(LanguageInterface::LANGCODE_NOT_SPECIFIED)
      ->willReturn($not_specified);

    $this->fieldTypePluginManager = $this->getMockBuilder('\Drupal\Core\Field\FieldTypePluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultStorageSettings')
      ->willReturn([]);
    $this->fieldTypePluginManager->expects($this->any())
      ->method('getDefaultFieldSettings')
      ->willReturn([]);

    $this->fieldItemList = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');
    $this->fieldTypePluginManager->expects($this->any())
      ->method('createFieldItemList')
      ->willReturn($this->fieldItemList);

    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('uuid', $this->uuid);
    $container->set('typed_data_manager', $this->typedDataManager);
    $container->set('language_manager', $this->languageManager);
    $container->set('plugin.manager.field.field_type', $this->fieldTypePluginManager);
    \Drupal::setContainer($container);

    $this->fieldDefinitions = [
      'id' => BaseFieldDefinition::create('integer'),
      'revision_id' => BaseFieldDefinition::create('integer'),
    ];
    $this->entityFieldManager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with($this->entityTypeId, $this->bundle)
      ->willReturn($this->fieldDefinitions);

    $this->entity = $this->getMockBuilder(ContentEntityBaseMockableClass::class)
      ->setConstructorArgs([$values, $this->entityTypeId, $this->bundle])
      ->onlyMethods([])
      ->getMock();

    $this->entityAdapter = EntityAdapter::createFromEntity($this->entity);
  }

  /**
   * @covers ::getConstraints
   */
  public function testGetConstraints() {
    $this->assertIsArray($this->entityAdapter->getConstraints());
  }

  /**
   * @covers ::getName
   */
  public function testGetName() {
    $this->assertNull($this->entityAdapter->getName());
  }

  /**
   * @covers ::getRoot
   */
  public function testGetRoot() {
    $this->assertSame(spl_object_hash($this->entityAdapter), spl_object_hash($this->entityAdapter->getRoot()));
  }

  /**
   * @covers ::getPropertyPath
   */
  public function testGetPropertyPath() {
    $this->assertSame('', $this->entityAdapter->getPropertyPath());
  }

  /**
   * @covers ::getParent
   */
  public function testGetParent() {
    $this->assertNull($this->entityAdapter->getParent());
  }

  /**
   * @covers ::setContext
   */
  public function testSetContext() {
    $name = $this->randomMachineName();
    $parent = $this->createMock('\Drupal\Core\TypedData\TraversableTypedDataInterface');
    // Our mocked entity->setContext() returns NULL, so assert that.
    $this->assertNull($this->entityAdapter->setContext($name, $parent));
    $this->assertEquals($name, $this->entityAdapter->getName());
    $this->assertEquals($parent, $this->entityAdapter->getParent());
  }

  /**
   * @covers ::getValue
   */
  public function testGetValue() {
    $this->assertEquals($this->entity, $this->entityAdapter->getValue());
  }

  /**
   * @covers ::getEntity
   */
  public function testGetEntity() {
    $this->assertSame($this->entity, $this->entityAdapter->getEntity());
  }

  /**
   * @covers ::setValue
   */
  public function testSetValue() {
    $this->entityAdapter->setValue(NULL);
    $this->assertNull($this->entityAdapter->getValue());
  }

  /**
   * @covers ::get
   */
  public function testGet() {
    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->entityAdapter->get('id'));
  }

  /**
   * @covers ::get
   */
  public function testGetInvalidField() {
    $this->expectException(\InvalidArgumentException::class);
    $this->entityAdapter->get('invalid');
  }

  /**
   * @covers ::get
   */
  public function testGetWithoutData() {
    $this->entityAdapter->setValue(NULL);
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->get('id');
  }

  /**
   * @covers ::set
   */
  public function testSet() {
    $id_items = [['value' => $this->id + 1]];

    $this->fieldItemList->expects($this->once())
      ->method('setValue')
      ->with($id_items);

    $this->entityAdapter->set('id', $id_items);
  }

  /**
   * @covers ::set
   */
  public function testSetWithoutData() {
    $this->entityAdapter->setValue(NULL);
    $id_items = [['value' => $this->id + 1]];
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->set('id', $id_items);
  }

  /**
   * @covers ::getProperties
   */
  public function testGetProperties() {
    $fields = $this->entityAdapter->getProperties();
    $this->assertInstanceOf('Drupal\Core\Field\FieldItemListInterface', $fields['id']);
    $this->assertInstanceOf('Drupal\Core\Field\FieldItemListInterface', $fields['revision_id']);
  }

  /**
   * @covers ::toArray
   */
  public function testToArray() {
    $array = $this->entityAdapter->toArray();
    // Mock field objects return NULL values, so test keys only.
    $this->assertArrayHasKey('id', $array);
    $this->assertArrayHasKey('revision_id', $array);
    $this->assertCount(2, $array);
  }

  /**
   * @covers ::toArray
   */
  public function testToArrayWithoutData() {
    $this->entityAdapter->setValue(NULL);
    $this->expectException(MissingDataException::class);
    $this->entityAdapter->toArray();
  }

  /**
   * @covers ::isEmpty
   */
  public function testIsEmpty() {
    $this->assertFalse($this->entityAdapter->isEmpty());
    $this->entityAdapter->setValue(NULL);
    $this->assertTrue($this->entityAdapter->isEmpty());
  }

  /**
   * @covers ::onChange
   */
  public function testOnChange() {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $entity->expects($this->once())
      ->method('onChange')
      ->with('foo')
      ->willReturn(NULL);
    $this->entityAdapter->setValue($entity);
    $this->entityAdapter->onChange('foo');
  }

  /**
   * @covers ::getDataDefinition
   */
  public function testGetDataDefinition() {
    $definition = $this->entityAdapter->getDataDefinition();
    $this->assertInstanceOf('\Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface', $definition);
    $this->assertEquals($definition->getEntityTypeId(), $this->entityTypeId);
    $this->assertEquals($definition->getBundles(), [$this->bundle]);
  }

  /**
   * @covers ::getString
   */
  public function testGetString() {
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $entity->expects($this->once())
      ->method('label')
      ->willReturn('foo');
    $this->entityAdapter->setValue($entity);
    $this->assertEquals('foo', $this->entityAdapter->getString());
    $this->entityAdapter->setValue(NULL);
    $this->assertEquals('', $this->entityAdapter->getString());
  }

  /**
   * @covers ::applyDefaultValue
   */
  public function testApplyDefaultValue() {
    // For each field on the entity the mock method has to be invoked once.
    $this->fieldItemList->expects($this->exactly(2))
      ->method('applyDefaultValue');
    $this->entityAdapter->applyDefaultValue();
  }

  /**
   * @covers ::getIterator
   */
  public function testGetIterator() {
    // Content entity test.
    $iterator = $this->entityAdapter->getIterator();
    $fields = iterator_to_array($iterator);
    $this->assertArrayHasKey('id', $fields);
    $this->assertArrayHasKey('revision_id', $fields);
    $this->assertCount(2, $fields);

    $this->entityAdapter->setValue(NULL);
    $this->assertEquals(new \ArrayIterator([]), $this->entityAdapter->getIterator());
  }

}
