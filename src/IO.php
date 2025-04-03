<?php

namespace Lawondyss\ParexCommander;

use Lawondyss\ParexCommander\Console\Confirmation;
use Lawondyss\ParexCommander\Console\Question;
use Lawondyss\ParexCommander\Console\Selection;
use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Lawondyss\ParexCommander\Console\Writer;
use Stringable;

use function explode;
use function str_pad;
use function str_repeat;
use function strlen;

use const STR_PAD_BOTH;

class IO
{
  public function __construct(
    private readonly Writer $writer = new Writer(),
  ) {
  }


  /**
   * @param string[] $yesOptions
   * @param string[] $noOptions
   */
  public function makeConfirmation(
    string $prompt,
    bool $default = true,
    array $yesOptions = Confirmation::DefaultYesOptions,
    array $noOptions = Confirmation::DefaultNoOptions,
  ): bool {
    return (new Confirmation($this->writer))->make($prompt, $default, $yesOptions, $noOptions);
  }


  /**
   * @param callable|null $validator fn(string): bool|string Custom error to display returned as string
   */
  public function makeQuestion(string $prompt, string $default = '', ?callable $validator = null): string
  {
    return (new Question($this->writer))->make($prompt, $default, $validator);
  }


  /**
   * @param list<string>|array<string, string> $options
   * @return string[]|string|null
   */
  public function makeSelection(string $prompt, array $options, bool $multiple = false): array|string|null
  {
    return (new Selection($this->writer))->make($prompt, $options, $multiple);
  }


  public function write(string|Stringable $message, string|Stringable ...$others): void
  {
    $this->writer->write($message, ...$others);
  }


  public function writeLn(string|Stringable $message, string|Stringable ...$others): void
  {
    $this->writer->writeLn($message, ...$others);
  }


  public function writeHeader(string $content): void
  {
    $leftSide = '* ';
    $rightSide = ' *';
    $lines = explode("\n", $content);
    $innerLength = max(array_map(strlen(...), $lines)) + 6;
    $outerLength = strlen($leftSide) + $innerLength + strlen($rightSide);

    $this->writeLn(str_repeat('*', $outerLength));

    foreach ($lines as $line) {
      $this->writeLn($leftSide, str_pad($line, $innerLength, pad_type: STR_PAD_BOTH), $rightSide);
    }

    $this->writeLn(str_repeat('*', $outerLength));
  }


  public function clearScreen(): void
  {
    $this->writer->write(Ansi::ClearScreen, Ansi::CursorHome);
  }
}
