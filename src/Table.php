<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit;

use Monolog\Logger;
use SetBased\Audit\MySql\DataLayer;

//--------------------------------------------------------------------------------------------------------------------
/**
 * Class for metadata of tables.
 */
class Table
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The metadata (additional) audit columns (as stored in the config file).
   *
   * @var Columns
   */
  private $myAuditColumns;

  /**
   * The name of the schema with the audit tables.
   *
   * @var string
   */
  private $myAuditSchema;

  /**
   * The name of the schema with the data tables.
   *
   * @var string
   */
  private $myDataSchema;

  /**
   * The metadata of the columns of the data table as stored in the config file.
   *
   * @var Columns
   */
  private $myDataTableColumnsConfig;

  /**
   * The metadata of the columns of the data table retrieved from information_schema.
   *
   * @var Columns
   */
  private $myDataTableColumnsDatabase;

  /**
   * Monolog
   *
   * @var Logger
   */
  private $myLog;

  /**
   * The name of the table.
   *
   * @var string
   */
  private $myTableName;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string  $theTableName             The table name.
   * @param Logger  $theLog                   Monolog
   * @param string  $theDataSchema            The name of the schema with data tables.
   * @param string  $theAuditSchema           The name of the schema with audit tables.
   * @param array[] $theConfigColumnsMetadata The columns of the data table as stored in the config file.
   * @param array[] $theAuditColumnsMetadata  The columns of the audit table as stored in the config file.
   */
  public function __construct($theTableName,
                              $theLog,
                              $theDataSchema,
                              $theAuditSchema,
                              $theConfigColumnsMetadata,
                              $theAuditColumnsMetadata)
  {
    $this->myTableName                = $theTableName;
    $this->myDataTableColumnsConfig   = new Columns($theConfigColumnsMetadata);
    $this->myLog                      = $theLog;
    $this->myDataSchema               = $theDataSchema;
    $this->myAuditSchema              = $theAuditSchema;
    $this->myDataTableColumnsDatabase = new Columns($this->getColumnsFromInformationSchema());
    $this->myAuditColumns             = new Columns($theAuditColumnsMetadata);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates missing audit table.
   */
  public function createMissingAuditTable()
  {
    $this->logInfo(sprintf('Creating audit table %s.', $this->myTableName));

    $columns = Columns::combine($this->myAuditColumns, $this->myDataTableColumnsDatabase);
    DataLayer::generateSqlCreateStatement($this->myAuditSchema, $this->myTableName, $columns->getColumns());
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates audit triggers on this table.
   */
  public function createTriggers()
  {
    // Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
    $this->lockTable($this->myTableName);

    // Drop all triggers, if any.
    $this->dropTriggers();

    // Create or recreate the audit triggers.
    $this->createTableTrigger($this->myTableName, 'INSERT');
    $this->createTableTrigger($this->myTableName, 'UPDATE');
    $this->createTableTrigger($this->myTableName, 'DELETE');

    // Insert, updates, and deletes are no audited again. So, release lock on the table.
    $this->unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the name of this table.
   *
   * @return string
   */
  public function getTableName()
  {
    return $this->myTableName;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Main function for work with table.
   *
   * @return Columns Columns for config file
   */
  public function main()
  {
    $compared_columns = null;
    if (isset($this->myDataTableColumnsConfig))
    {
      $compared_columns = $this->compareTableColumnsConfig();
    }

    $altered_columns_types = [];
    if (empty($compared_columns['new_columns']) && empty($compared_columns['obsolete_columns']))
    {
      $altered_columns_types = $this->compareColumnsTypesConfig();
      if (empty($altered_columns_types))
      {
        $this->createTriggers();
      }
    }

    return ['columns'         => $compared_columns['full_columns'],
            'altered_columns' => $altered_columns_types];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Adds new columns to audit table.
   *
   * @param array[] $theColumns     Columns array
   * @param string  $theAfterColumn After which column add new columns
   */
  private function addNewColumns($theColumns, $theAfterColumn)
  {
    DataLayer::addNewColumns($this->myAuditSchema, $this->myTableName, $theColumns, $theAfterColumn);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compares columns types from table in data_schema with columns in config file. 
   *
   * @return array[]
   */
  private function compareColumnsTypesConfig()
  {
    $altered_columns_types = Columns::differentColumnTypes($this->myDataTableColumnsDatabase, 
                                                           $this->myDataTableColumnsConfig);
    foreach ($altered_columns_types as $column)
    {
      $this->logInfo(sprintf('Type of %s.%s has been altered to %s',
                             $this->myTableName,
                             $column['column_name'],
                             $column['column_type']));
    }

    return $altered_columns_types;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Compare columns from table in data_schema with columns in config file
   *
   * @return array[]
   */
  private function compareTableColumnsConfig()
  {
    $column_actual  = new Columns(DataLayer::getTableColumns($this->myAuditSchema, $this->myTableName));
    $columns_config = Columns::combine($this->myAuditColumns, $this->myDataTableColumnsConfig);
    $columns_target = Columns::combine($this->myAuditColumns, $this->myDataTableColumnsDatabase);

    $new_columns      = Columns::notInOtherSet($columns_target, $column_actual);
    $obsolete_columns = Columns::notInOtherSet($columns_config, $columns_target);

    if (!empty($new_columns) && !empty($obsolete_columns))
    {
      $this->logInfo(sprintf('Found both new and obsolete columns for table %s', $this->myTableName));
      $this->logInfo(sprintf('No action taken.'));
      foreach ($new_columns as $column)
      {
        $this->logInfo(sprintf('New column %s', $column['column_name']));
      }
      foreach ($obsolete_columns as $column)
      {
        $this->logInfo(sprintf('Obsolete column %s', $column['column_name']));
      }

      return ['full_columns'     => $this->myDataTableColumnsConfig,
              'new_columns'      => $new_columns,
              'obsolete_columns' => $obsolete_columns];
    }

    foreach ($obsolete_columns as $column)
    {
      $this->logInfo(sprintf('Obsolete column %s.%s', $this->myTableName, $column['column_name']));
    }

    foreach ($new_columns as $new_column)
    {
      $this->logInfo(sprintf('New column %s.%s', $this->myTableName, $new_column['column_name']));
    }
    $this->addNewColumns($new_columns, 'audit_usr_id');

    return ['full_columns'     => $this->myDataTableColumnsDatabase,
            'new_columns'      => $new_columns,
            'obsolete_columns' => $obsolete_columns];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Creates a triggers for this table.
   *
   * @param string $theTableName The name of table
   * @param string $theAction    Trigger ON action {INSERT, DELETE, UPDATE}
   */
  private function createTableTrigger($theTableName, $theAction)
  {
    $this->logVerbose(sprintf('Create %s trigger for table %s.', $theAction, $theTableName));
    $trigger_name = $this->getTriggerName($this->myDataSchema, $theAction);
    DataLayer::createTrigger($this->myDataSchema,
                             $this->myAuditSchema,
                             $theTableName,
                             $theAction,
                             $trigger_name);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Drops all triggers from this table.
   */
  private function dropTriggers()
  {
    $triggers = DataLayer::getTableTriggers($this->myDataSchema, $this->myTableName);
    foreach ($triggers as $trigger)
    {
      $this->logVerbose(sprintf('Drop trigger %s for table %s.', $trigger['Trigger_Name'], $this->myTableName));

      DataLayer::dropTrigger($trigger['Trigger_Name']);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects and returns the metadata of the columns of this table from information_schema.
   *
   * @return array[]
   */
  private function getColumnsFromInformationSchema()
  {
    $result = DataLayer::getTableColumns($this->myDataSchema, $this->myTableName);

    return $result;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Create and return trigger name.
   *
   * @param string $theDataSchema Database data schema
   * @param string $theAction     Trigger on action (Insert, Update, Delete)
   *
   * @return string
   */
  private function getTriggerName($theDataSchema, $theAction)
  {
    $uuid = uniqid('trg_');

    return strtolower(sprintf('`%s`.`%s_%s`', $theDataSchema, $uuid, $theAction));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Lock the table to prevent insert, updates, or deletes between dropping and creating triggers.
   *
   * @param string $theTableName Name of table
   */
  private function lockTable($theTableName)
  {
    DataLayer::lockTable($theTableName);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Log function
   *
   * @param string $theMessage Message for print in console
   */
  private function logInfo($theMessage)
  {
    $this->myLog->addNotice($theMessage);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Log verbose
   *
   * @param string $theMessage Message for print in console
   */
  private function logVerbose($theMessage)
  {
    $this->myLog->addInfo($theMessage);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Insert, updates, and deletes are no audited again. So, release lock on the table.
   */
  private function unlockTables()
  {
    DataLayer::unlockTables();
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------