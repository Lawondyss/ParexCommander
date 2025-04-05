<?php

namespace Lawondyss\ParexCommander\Console;

use Stringable;

use function implode;

use const PHP_EOL;

class Writer
{
  public function write(string|Stringable $message, string|Stringable ...$others): void
  {
    echo $message . implode('', $others);
  }


  public function writeLn(string|Stringable $message = '', string|Stringable ...$others): void
  {
    $others[] = PHP_EOL;
    $this->write($message, ...$others);
  }
}
