<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\MySql\Command;

use SetBased\Audit\MySql\AuditAlter;
use SetBased\Stratum\Style\StratumStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//----------------------------------------------------------------------------------------------------------------------
/**
 * Command for comparing data tables with audit tables.
 */
class AlterAuditTableCommand extends AuditCommand
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Check full full and return array without new or obsolete columns if full not set.
   *
   * @param array[] $columns The metadata of the columns of a table.
   *
   * @var StratumStyle
   */
  protected $io;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this->setName('alter-audit-table')
         ->setDescription('Creates alter SQL statements for audit tables')
         ->addArgument('config file', InputArgument::REQUIRED, 'The audit configuration file')
         ->addArgument('sql file', InputArgument::REQUIRED, 'The destination file for the SQL statements');

    $this->setHelp(<<<EOL
Generates alter table SQL statements for aligning the audit tables with the 
audit configuration file and data tables.

Manual inspection of the generated SQL statements is required. For example: 
changing an audit column from varchar(20) character set utf8 to varchar(10)
character set ascii might cause problems when the audit column has values
longer than 10 characters or values outside the ASCII character set (even 
though the current data table hold only values with length 10 or less and 
only in the ASCII character set).

No SQL statements will be generated for missing or obsolete columns in the 
audit tables. Use the command 'audit' for creating missing columns in audit
tables.
EOL
    );
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->io = new StratumStyle($input, $output);

    $resultSqlFile = $input->getArgument('sql file');

    $this->configFileName = $input->getArgument('config file');
    $this->readConfigFile();

    $this->connect($this->config);

    $alter   = new AuditAlter($this->config, $this->configMetadata);
    $content = $alter->main();

    $this->writeTwoPhases($resultSqlFile, $content);
  }

  //--------------------------------------------------------------------------------------------------------------------

}

//----------------------------------------------------------------------------------------------------------------------
