<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Exception;
use Lawondyss\ParexCommander\IO;

class TestExitException extends Exception
{
}

class TestIO extends IO
{
  public function exitSuccess(): never
  {
    throw new TestExitException('exitSuccess', 0);
  }


  public function exitError(int $code = 1): never
  {
    throw new TestExitException('exitError', $code);
  }
}
