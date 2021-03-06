<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Test\MySql\AuditCommand\AddColumn;

use SetBased\Audit\MySql\AuditDataLayer;
use SetBased\Audit\Test\MySql\AuditCommand\AuditCommandTestCase;
use SetBased\Stratum\MySql\StaticDataLayer;

/**
 * Tests for running audit with a new table column.
 */
class AddColumnTest extends AuditCommandTestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass()
  {
    self::$dir = __DIR__;

    parent::setUpBeforeClass();
  }

  //--------------------------------------------------------------------------------------------------------------------
  public function test01()
  {
    // Run audit.
    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));

    $actual = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    $expected   = [];
    $expected[] = ['column_name'        => 'c1',
                   'column_type'        => 'tinyint(4)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c2',
                   'column_type'        => 'smallint(6)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    self::assertSame($expected, $actual);

    // Create new column.
    StaticDataLayer::multiQuery(file_get_contents(__DIR__.'/config/create_new_column.sql'));

    $this->runAudit();

    // TABLE1 MUST exist.
    $tables = AuditDataLayer::getTablesNames(self::$auditSchema);
    self::assertNotNull(StaticDataLayer::searchInRowSet('table_name', 'TABLE1', $tables));

    // TABLE1 MUST have triggers.
    $triggers = AuditDataLayer::getTableTriggers(self::$dataSchema, 'TABLE1');
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_insert', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_update', $triggers));
    self::assertNotNull(StaticDataLayer::searchInRowSet('trigger_name', 'trg_audit_t1_delete', $triggers));

    // TABLE1 must have column c3.
    $actual = AuditDataLayer::getTableColumns(self::$auditSchema, 'TABLE1');

    $expected   = [];
    $expected[] = ['column_name'        => 'c1',
                   'column_type'        => 'tinyint(4)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c2',
                   'column_type'        => 'smallint(6)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c3',
                   'column_type'        => 'mediumint(9)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];
    $expected[] = ['column_name'        => 'c4',
                   'column_type'        => 'int(11)',
                   'is_nullable'        => 'YES',
                   'character_set_name' => null,
                   'collation_name'     => null];

    self::assertSame($expected, $actual);

    // Test triggers.
    StaticDataLayer::query('insert into `TABLE1`(c1, c2, c3, c4) values(1,  2, 3, 4)');
    StaticDataLayer::query('update `TABLE1` set c1=10, c2=20, c3=30, c4=40');
    StaticDataLayer::query('delete from `TABLE1`');

    $rows = StaticDataLayer::executeRows(sprintf('select * from `%s`.`TABLE1` where c3 is not null',
                                                 self::$auditSchema));
    self::assertSame(4, count($rows), 'row_count');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
