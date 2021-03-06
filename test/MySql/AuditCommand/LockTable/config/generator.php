#!/usr/bin/env php
<?php
//----------------------------------------------------------------------------------------------------------------------

use SetBased\Affirm\ErrorHandler;
use SetBased\Stratum\MySql\StaticDataLayer;

//----------------------------------------------------------------------------------------------------------------------
$files = [__DIR__.'/../vendor/autoload.php',
          __DIR__.'/../../vendor/autoload.php',
          __DIR__.'/../../../vendor/autoload.php',
          __DIR__.'/../../../../vendor/autoload.php',
          __DIR__.'/../../../../../vendor/autoload.php'];

foreach ($files as $file)
{
  if (file_exists($file))
  {
    require $file;
    break;
  }
}

declare(ticks = 1);

//----------------------------------------------------------------------------------------------------------------------
function signalHandler()
{
  $GLOBALS['exit'] = true;
}

//----------------------------------------------------------------------------------------------------------------------
$GLOBALS['exit'] = false;

pcntl_signal(SIGUSR1, "signalHandler");

// Set error handler.
$handler = new ErrorHandler();
$handler->register();

StaticDataLayer::connect('localhost', 'test', 'test', 'test_data');

while (true)
{
  if ($GLOBALS['exit']) break;

  StaticDataLayer::begin();
  StaticDataLayer::executeNone('insert into TABLE1(c) values(1)');
  StaticDataLayer::executeNone('update TABLE1 set c = 2');
  StaticDataLayer::executeNone('delete from TABLE1 where c = 2');
  StaticDataLayer::commit();
}

StaticDataLayer::disconnect();
