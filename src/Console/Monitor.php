<?php

namespace Lawondyss\ParexCommander\Console;

use Closure;
use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Stringable;

use function array_key_last;
use function array_shift;
use function array_unshift;
use function count;
use function implode;
use function str_ends_with;
use function str_repeat;

use const PHP_EOL;

class Monitor
{
  /** @var string[] */
  private array $buffer = [];


  public function __construct(
    private readonly Writer $writer,
    private readonly int $maxLines,
  ) {
  }


  /**
   * @param callable $callback fn(Writer): mixed
   * @return mixed Value from callback
   */
  public function execute(string $label, callable $callback): mixed
  {
    $this->writer->writeLn(":> $label");
    // We need to create a space where the output will be dumped.
    $this->writer->write(str_repeat(PHP_EOL, $this->maxLines));

    $result = $callback($this->monitoredWriter($this->write(...)));

    $this->clearDisplay();

    return $result;
  }


  private function monitoredWriter(Closure $write): Writer
  {
    return new class ($write) extends Writer {
      public function __construct(private readonly Closure $write)
      {
      }


      public function write(Stringable|string $message = '', string|Stringable ...$others): void
      {
        $line = $message . implode('', $others);
        ($this->write)($line);
      }
    };
  }


  /**
   * It writes to the buffer, from where it is then written out to the reserved space.
   * It is used to overload Writer internally and inject it into the callback.
   */
  private function write(string $line): void
  {
    $lastIndex = array_key_last($this->buffer);
    $lastLine = $this->buffer[$lastIndex] ?? PHP_EOL;

    if (!str_ends_with($lastLine, PHP_EOL)) {
      $this->buffer[$lastIndex] .= $line;
    } else {
      $this->buffer[] = $line;

      if (count($this->buffer) > $this->maxLines) {
        array_shift($this->buffer);
      }
    }

    $this->refreshDisplay();
  }


  private function refreshDisplay(): void
  {
    $this->writer->write(Ansi::cursorUpAndStart($this->maxLines));

    for ($i = 0; $i < $this->maxLines; $i++) {
      $this->writer->write(Ansi::ClearLine);

      if (isset($this->buffer[$i])) {
        $this->writer->writeLn(' > ', rtrim($this->buffer[$i], PHP_EOL));
      } else {
        $this->writer->writeLn();
      }
    }
  }


  private function clearDisplay(): void
  {
    $this->writer->write(Ansi::cursorUpAndStart($this->maxLines));
    $this->writer->write(Ansi::ClearDown);
  }
}
