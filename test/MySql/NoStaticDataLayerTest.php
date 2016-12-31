<?php
//----------------------------------------------------------------------------------------------------------------------
namespace SetBased\Audit\Test\MySql;

//----------------------------------------------------------------------------------------------------------------------
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * Test StaticDataLayer is not used in the sources of PhpAudit.
 */
class NoStaticDataLayerTest extends \PHPUnit_Framework_TestCase
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The actual test.
   */
  public function testNoStaticDataLayer()
  {
    $files = $this->findPhpFiles();
    $this->assertNotEmpty($files);

    foreach ($files as $file)
    {
      $source = file_get_contents($file);
      $pos    = strpos($source, 'StaticDataLayer');
      $this->assertFalse($pos, sprintf('Found usage of StaticDataLayer in %s', $file));
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Finds all PHP sources files.
   */
  private function findPhpFiles()
  {
    // Get the class loader.
    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = spl_autoload_functions()[0][0];

    $audit_data_layer_path = $loader->findFile('SetBased\\Audit\\MySql\\AuditDataLayer');
    $dir                   = realpath(dirname($audit_data_layer_path).DIRECTORY_SEPARATOR.'..');

    $directory = new RecursiveDirectoryIterator($dir);
    $iterator  = new RecursiveIteratorIterator($directory);
    $regex     = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    $files = [];
    foreach ($regex as $name => $object)
    {
      if ($name!=$audit_data_layer_path)
      {
        $files[] = $name;
      }
    }

    return $files;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
