<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Kernel;

use Drupal\media\Entity\Media;

/**
 * Tests Media.
 *
 * @group media
 */
class MediaTest extends MediaKernelTestBase {

  /**
   * Tests various aspects of a media item.
   */
  public function testEntity() {
    $media = Media::create(['bundle' => $this->testMediaType->id()]);

    $this->assertSame($media, $media->setOwnerId($this->user->id()), 'setOwnerId() method returns its own entity.');
  }

  /**
   * Tests the Media "name" base field behavior.
   */
  public function testNameBaseField() {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $field_definitions */
    $field_definitions = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('media');

    // Ensure media name is configurable on manage display.
    $this->assertTrue($field_definitions['name']->isDisplayConfigurable('view'));
    // Ensure it is not visible by default.
    $this->assertSame($field_definitions['name']->getDisplayOptions('view'), ['region' => 'hidden']);
  }

  /**
   * Tests permissions based on a media type have the correct permissions.
   */
  public function testPermissions() {
    $permissions = $this->container->get('user.permissions')->getPermissions();
    $name = "create {$this->testMediaType->id()} media";
    $this->assertArrayHasKey($name, $permissions);
    $this->assertSame(['config' => [$this->testMediaType->getConfigDependencyName()]], $permissions[$name]['dependencies']);
  }

}
