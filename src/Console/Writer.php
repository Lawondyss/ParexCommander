<?php

namespace Lawondyss\ParexCommander\Console;

use Stringable;

use function array_unshift;
use function implode;

use const PHP_EOL;

class Writer
{
  public function write(string|Stringable $message, string|Stringable ...$others): void
  {
    array_unshift($others, $message);
    echo implode('', $others);
  }


  public function writeLn(string|Stringable $message, string|Stringable ...$others): void
  {
    $others[] = PHP_EOL;
    $this->write($message, ...$others);
  }
}
