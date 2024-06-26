<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

/**
 * Tests handling case sensitive collation.
 *
 * @group Database
 */
class CaseSensitivityTest extends DatabaseTestBase {

  /**
   * Tests BINARY collation in MySQL.
   */
  public function testCaseSensitiveInsert() {
    $num_records_before = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();

    $this->connection->insert('test')
      ->fields([
        // A record already exists with name 'John'.
        'name' => 'john',
        'age' => 2,
        'job' => 'Baby',
      ])
      ->execute();

    $num_records_after = $this->connection->query('SELECT COUNT(*) FROM {test}')->fetchField();
    $this->assertSame($num_records_before + 1, (int) $num_records_after, 'Record inserts correctly.');
    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'john'])->fetchField();
    $this->assertSame('2', $saved_age, 'Can retrieve after inserting.');
  }

}
