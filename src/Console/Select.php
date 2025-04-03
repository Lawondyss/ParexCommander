<?php

namespace Lawondyss\ParexCommander\Console;

use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Lawondyss\ParexCommander\Console\Utils\Key;

use function array_diff;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_shift;
use function count;
use function fread;
use function in_array;
use function register_shutdown_function;
use function stream_select;
use function substr_count;
use function system;

use const STDIN;

class Select
{
  private const ReadTimeout = 200_000; // microseconds

  private bool $terminalStateAltered = false;


  public function __construct()
  {
    // Handler for clean shutdown in case of exceptions or interruptions
    register_shutdown_function($this->restoreTerminalState(...));
  }


  public function __destruct()
  {
    $this->restoreTerminalState();
  }


  /**
   * @param array<array-key, string> $options
   * @return array<string|int>|string|int|null
   */
  public function make(string $prompt, array $options, bool $multiple = false): array|string|int|null
  {
    if ($options === []) {
      return $multiple ? [] : null;
    }

    $selectedIndex = 0;
    $optionsCount = count($options);
    $selectedOptions = [];
    $linesCount = 0;

    $this->setupTerminalState();

    // Infinite loop until the user confirms the selection
    $firstRun = true;

    do {
      if (!$firstRun) {
        // Redraw the screen with actual state
        $this->moveCursorUp($linesCount);
        $this->clearDown();
      }

      $firstRun = false;

      // Display the menu and calculate the number of lines
      $output = $this->render($prompt, $options, $selectedIndex, $selectedOptions, $multiple);
      $linesCount = substr_count($output, PHP_EOL);

      echo $output;

      $input = $this->readKeypress();
      $done = $this->processInput(
        $input,
        $selectedIndex,
        $selectedOptions,
        $optionsCount,
        $multiple
      );
    } while ($done === false);

    $this->restoreTerminalState();

    return $this->formatResult($options, $selectedOptions, $multiple);

  }


  private function setupTerminalState(): void
  {
    if (!$this->terminalStateAltered) {
      // Disable echo - do not display pressed keys
      system('stty -echo');
      // Disable canonical mode - read character by character
      system('stty -icanon');

      $this->terminalStateAltered = true;
    }
  }


  public function restoreTerminalState(): void
  {
    if ($this->terminalStateAltered) {
      // Enable echo - display pressed keys
      system('stty echo');
      // Enable canonical mode - read line by line
      system('stty icanon');

      $this->terminalStateAltered = false;
    }
  }


  private function moveCursorUp(int $lines): void
  {
    if ($lines > 0) {
      echo Ansi::cursorUpAndStart($lines);
    }
  }


  private function clearDown(): void
  {
    echo Ansi::ClearDown;
  }


  /**
   * @param array<array-key, string> $options
   * @param array<array-key> $selectedOptions
   */
  private function render(
    string $prompt,
    array $options,
    int $selectedIndex,
    array $selectedOptions,
    bool $multiple,
  ): string {
    $output = $prompt . PHP_EOL;
    $indexes = array_keys($options);
    $selectedIndexes = array_intersect_key($indexes, array_flip($selectedOptions));

    foreach ($options as $index => $option) {
      $isSelected = in_array($index, $selectedIndexes, strict: true);
      $isCursor = ($index === ($indexes[$selectedIndex]));

      $prefix = $isCursor
        ? ' » '
        : '   ';

      $checkbox = $multiple
        ? ($isSelected ? '[×] ' : '[ ] ')
        : '• ';

      $output .= $prefix . $checkbox . $option . PHP_EOL;
    }

    $output .= PHP_EOL;
    $output .= $multiple
      ? 'Use the up/down arrow keys to navigate, space to select, and Enter to confirm.' . PHP_EOL
      : 'Use the up/down arrow keys to navigate and Enter to select.' . PHP_EOL;

    return $output;
  }


  /**
   * @param array<array-key> &$selectedOptions
   * @return bool User confirmed the selection, exit the loop
   */
  private function processInput(
    string $input,
    int &$selectedIndex,
    array &$selectedOptions,
    int $optionsCount,
    bool $multiple,
  ): bool {
    return match ($input) {
      Key::ArrowUp => $this->handleArrowUp($selectedIndex, $optionsCount),
      Key::ArrowDown => $this->handleArrowDown($selectedIndex, $optionsCount),
      Key::Space => $this->handleSpace($selectedIndex, $selectedOptions, $multiple),
      Key::Enter1, Key::Enter2 => $this->handleEnter($selectedIndex, $selectedOptions, $multiple),
      default => false,
    };
  }


  private function handleArrowUp(int &$selectedIndex, int $optionsCount): bool
  {
    $selectedIndex = ($selectedIndex > 0)
      ? $selectedIndex - 1
      : $optionsCount - 1;

    return false;
  }


  private function handleArrowDown(int &$selectedIndex, int $optionsCount): bool
  {
    $selectedIndex = ($selectedIndex < $optionsCount - 1)
      ? $selectedIndex + 1
      : 0;

    return false;
  }


  /**
   * @param array<array-key> &$selectedOptions
   */
  private function handleSpace(int $selectedIndex, array &$selectedOptions, bool $multiple): bool
  {
    if (!$multiple) {
      return false;
    }

    // Toggle the selection of the item
    in_array($selectedIndex, $selectedOptions)
      ? $selectedOptions = array_diff($selectedOptions, [$selectedIndex])
      : $selectedOptions[] = $selectedIndex;

    return false;
  }


  /**
   * @param array<array-key> &$selectedOptions
   */
  private function handleEnter(int $selectedIndex, array &$selectedOptions, bool $multiple): bool
  {
    // For single selection always select the current item
    !$multiple && $selectedOptions = [$selectedIndex];

    return true;
  }


  /**
   * @param array<array-key, string> $options
   * @param array<array-key> $selectedOptions
   * @return array<array-key>|array-key|null
   */
  private function formatResult(array $options, array $selectedOptions, bool $multiple): array|string|int|null
  {
    $keys = array_keys($options);
    $result = [];

    foreach ($selectedOptions as $index) {
      $result[] = $keys[$index];
    }

    return !$multiple ? array_shift($result) : $result;
  }


  private function readKeypress(): string
  {
    $input = '';
    $char = $this->readChar();
    $input .= $char;

    // If the character is escape, we need to read the rest of the sequence
    if ($char === Ansi::Esc) {
      $char = $this->readChar();
      $input .= $char;

      // If the second character is "[", we expected another character
      if ($char === '[') {
        $input .= $this->readChar();
      }
    }

    return $input;
  }


  private function readChar(): string|null
  {
    $char = false;

    $read = [STDIN];
    $write = $except = [];

    if (stream_select($read, $write, $except, seconds: 0, microseconds: self::ReadTimeout)) {
      $char = fread(STDIN, length: 1);
    }

    return ($char !== false) ? $char : null;
  }
}
