<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql;

use Monolog\Logger;
use SetBased\Stratum\MySql\StaticDataLayer;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Class for executing SQL statements and retrieving metadata from MySQL.
 */
class DataLayer extends StaticDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The additional SQL statements.
   *
   * @var array[]
   */
  protected static $ourAdditionalSql;

  /**
   * Logger.
   *
   * @var Logger
   */
  private static $ourLogger;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to an audit table.
   *
   * @param string $theAuditSchemaName The name of audit schema.
   * @param string $theTableName       The name of the table.
   * @param array  $theColumns         The metadata of the new columns.
   */
  public static function addNewColumns($theAuditSchemaName, $theTableName, $theColumns)
  {
    $sql = sprintf('alter table `%s`.`%s`', $theAuditSchemaName, $theTableName);
    foreach ($theColumns as $column)
    {
      $sql .= ' add `'.$column['column_name'].'` '.$column['column_type'];
      if (isset($column['after']))
      {
        $sql .= ' after `'.$column['after'].'`';
      }
      else
      {
        $sql .= ' first';
      }
      if (end($theColumns)!==$column)
      {
        $sql .= ',';
      }
    }

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops a trigger in the current schema.
   *
   * @param string $theTriggerName Name of trigger
   */
  public static function dropTrigger($theTriggerName)
  {
    $sql = sprintf('drop trigger `%s`', $theTriggerName);

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query and logs the result set.
   *
   * @param string $theQuery The query or multi query.
   *
   * @return int The total number of rows selected/logged.
   */
  public static function executeLog($theQuery)
  {
    self::$ourLogger->addDebug(sprintf('Executing query: %s', $theQuery));

    return parent::executeLog($theQuery);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @param string $theQuery The SQL statement.
   *
   * @return int The number of affected rows (if any).
   */
  public static function executeNone($theQuery)
  {
    self::$ourLogger->addDebug(sprintf('Executing query: %s', $theQuery));

    return parent::executeNone($theQuery);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or 1 row.
   * Throws an exception if the query selects 2 or more rows.
   *
   * @param string $theQuery The SQL statement.
   *
   * @return array|null The selected row.
   */
  public static function executeRow0($theQuery)
  {
    self::$ourLogger->addDebug(sprintf('Executing query: %s', $theQuery));

    return parent::executeRow0($theQuery);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 1 and only 1 row.
   * Throws an exception if the query selects none, 2 or more rows.
   *
   * @param string $theQuery The SQL statement.
   *
   * @return array The selected row.
   */
  public static function executeRow1($theQuery)
  {
    self::$ourLogger->addDebug(sprintf('Executing query: %s', $theQuery));

    return parent::executeRow1($theQuery);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Executes a query that returns 0 or more rows.
   *
   * @param string $theQuery The SQL statement.
   *
   * @return array[] The selected rows.
   */
  public static function executeRows($theQuery)
  {
    self::$ourLogger->addDebug(sprintf('Executing query: %s', $theQuery));

    return parent::executeRows($theQuery);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects metadata of all columns of table.
   *
   * @param string $theSchemaName The name of the table schema.
   * @param string $theTableName  The name of the table.
   *
   * @return array[]
   */
  public static function getTableColumns($theSchemaName, $theTableName)
  {
    $sql = sprintf('
select COLUMN_NAME as column_name
,      COLUMN_TYPE as column_type
from   information_schema.COLUMNS
where  TABLE_SCHEMA = %s
and    TABLE_NAME   = %s
order by ORDINAL_POSITION',
                   self::quoteString($theSchemaName),
                   self::quoteString($theTableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all triggers on a table.
   *
   * @param string $theSchemaName The name of the table schema.
   * @param string $theTableName  The name of the table.
   *
   * @return array[]
   */
  public static function getTableTriggers($theSchemaName, $theTableName)
  {
    $sql = sprintf('
select
  Trigger_Name
from
	information_schema.TRIGGERS
where
	TRIGGER_SCHEMA = %s
  and
  EVENT_OBJECT_TABLE = %s',
                   self::quoteString($theSchemaName),
                   self::quoteString($theTableName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects all table names in a schema.
   *
   * @param string $theSchemaName The name of the schema.
   *
   * @return array[]
   */
  public static function getTablesNames($theSchemaName)
  {
    $sql = sprintf("
select TABLE_NAME AS table_name
from   information_schema.TABLES
where  TABLE_SCHEMA = %s
and    TABLE_TYPE   = 'BASE TABLE'
order by TABLE_NAME", self::quoteString($theSchemaName));

    return self::executeRows($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Acquires a write lock on a table.
   *
   * @param string $theTableName The table name.
   */
  public static function lockTable($theTableName)
  {
    $sql = sprintf('lock tables `%s` write', $theTableName);

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Set addition SQL code.
   *
   * @param array[] $theAdditionalSQL
   */
  public static function setAdditionalSQL($theAdditionalSQL)
  {
    self::$ourAdditionalSql = $theAdditionalSQL;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setter for logger.
   *
   * @param Logger $theLogger
   */
  public static function setLog($theLogger)
  {
    self::$ourLogger = $theLogger;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Releases all table locks.
   */
  public static function unlockTables()
  {
    $sql = 'unlock tables';

    self::executeNone($sql);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
