<?php

namespace Lawondyss\ParexCommander\Console;

use function fgets;
use function strtoupper;
use function trim;

use const STDIN;

class Confirmation
{
  public const DefaultYesOptions = ['y', 'yes'];
  public const DefaultNoOptions = ['n', 'no'];

  public function __construct(
    private readonly Writer $writer,
  ) {
  }


  /**
   * @param string[] $yesOptions
   * @param string[] $noOptions
   */
  public function make(
    string $prompt,
    bool $default = true,
    array $yesOptions = self::DefaultYesOptions,
    array $noOptions = self::DefaultNoOptions,
  ): bool {
    // Comparison is case-insensitive
    $yesOptions = array_map(strtolower(...), $yesOptions);
    $noOptions = array_map(strtolower(...), $noOptions);

    // Display only first option
    $yesDisplay = $yesOptions[0];
    $noDisplay = $noOptions[0];

    // Show default option as uppercase
    $default
      ? $yesDisplay = strtoupper($yesDisplay)
      : $noDisplay = strtoupper($noDisplay);

    $options = "[$yesDisplay/$noDisplay]";

    while (true) {
      $this->writer->write("{$prompt} {$options} ");
      $answer = trim(fgets(STDIN));

      if ($answer === '') {
        return $default;
      }

      $answer = strtolower($answer);

      if (in_array($answer, $yesOptions)) {
        return true;
      } elseif (in_array($answer, $noOptions)) {
        return false;
      }

      // Showing a hint for an incorrect answer
      $this->writer->writeLn('Please enter one of the options: ', implode(', ', [...$yesOptions, ...$noOptions]));
    }
  }
}
