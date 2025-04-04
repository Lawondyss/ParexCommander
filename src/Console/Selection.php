<?php

namespace Lawondyss\ParexCommander\Console;

use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Lawondyss\ParexCommander\Console\Utils\Key;
use Lawondyss\ParexCommander\Exception\InvalidArgumentException;

use function array_diff;
use function array_flip;
use function array_intersect_key;
use function array_is_list;
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

class Selection
{
  private const ReadTimeout = 200_000; // microseconds

  private bool $terminalStateAltered = false;


  public function __construct(
    private readonly Writer $writer,
  ) {
    // Handler for clean shutdown in case of exceptions or interruptions
    register_shutdown_function($this->restoreTerminalState(...));
  }


  public function __destruct()
  {
    $this->restoreTerminalState();
  }


  /**
   * @param list<string>|array<string, string> $options
   * @return string[]|string List returns value(s), map returns key(s)
   */
  public function make(string $prompt, array $options, bool $multiple = false, bool $require = true): array|string
  {
    $options === [] && throw new InvalidArgumentException('Options for selection cannot be empty.');

    $position = 0;
    $optionsCount = count($options);
    $selectedPositions = [];
    $linesCount = 0;
    $error = null;

    // Infinite loop until the user confirms the selection
    $firstRun = true;

    // Label for goto
    startLoop:

    $this->setupTerminalState();

    do {
      if (!$firstRun) {
        // Redraw the screen with actual state
        $this->moveCursorUp($linesCount);
        $this->clearDown();
      }

      $firstRun = false;

      // Display the menu and calculate the number of lines for redrawing
      $output = $this->createOutput($prompt, $options, $position, $selectedPositions, $multiple, $error);
      $linesCount = substr_count($output, PHP_EOL);

      $this->writer->write($output);

      $input = $this->readKeypress();
      $done = $this->processInput(
        $input,
        $error,
        $position,
        $selectedPositions,
        $optionsCount,
        $multiple
      );
    } while ($done === false);

    $this->restoreTerminalState();

    $result = $this->extractResult($options, $selectedPositions, $multiple);

    if (empty($result) && $require) {
      $error = 'Select some option.';
      goto startLoop;
    }


    // Clear hint and possibly error message
    $this->moveCursorUp(1 + (int)isset($error));
    $this->clearDown();

    return $result;
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
      $this->writer->write(Ansi::cursorUpAndStart($lines));
    }
  }


  private function clearDown(): void
  {
    $this->writer->write(Ansi::ClearDown);
  }


  /**
   * @param array<array-key, string> $options
   * @param list<int> $selectedPositions
   */
  private function createOutput(
    string $prompt,
    array $options,
    int $position,
    array $selectedPositions,
    bool $multiple,
    ?string $error,
  ): string {
    $output = $prompt . PHP_EOL;

    // Because options can be associate array, we need own numeric index
    $index = 0;

    foreach ($options as $option) {
      $isSelected = in_array($index, $selectedPositions, strict: true);
      $isCursor = $index++ === $position;

      $prefix = $isCursor
        ? ' » '
        : '   ';

      $checkbox = $multiple
        ? ($isSelected ? '[×] ' : '[ ] ')
        : '• ';

      $output .= $prefix . $checkbox . $option . PHP_EOL;
    }

    $output .= $multiple
      ? 'Use the up/down arrow keys to navigate, space to select, and Enter to confirm.' . PHP_EOL
      : 'Use the up/down arrow keys to navigate and Enter to select.' . PHP_EOL;

    $error && ($output .= $error . PHP_EOL);

    return $output;
  }


  /**
   * @param array<array-key> &$selectedPositions
   * @return bool User confirmed the selection, exit the loop
   */
  private function processInput(
    string $input,
    ?string &$error,
    int &$position,
    array &$selectedPositions,
    int $optionsCount,
    bool $multiple,
  ): bool {
    return match ($input) {
      Key::ArrowUp => $this->handleArrowUp($position, $optionsCount),
      Key::ArrowDown => $this->handleArrowDown($position, $optionsCount),
      Key::Space => $this->handleSpace($position, $error, $selectedPositions, $multiple),
      Key::Enter1, Key::Enter2 => $this->handleEnter($position, $selectedPositions, $multiple),
      default => false,
    };
  }


  private function handleArrowUp(int &$position, int $optionsCount): bool
  {
    $position = ($position > 0)
      ? $position - 1
      : $optionsCount - 1;

    return false;
  }


  private function handleArrowDown(int &$position, int $optionsCount): bool
  {
    $position = ($position < $optionsCount - 1)
      ? $position + 1
      : 0;

    return false;
  }


  /**
   * @param list<int> &$selectedPositions
   */
  private function handleSpace(int $position, ?string &$error, array &$selectedPositions, bool $multiple): bool
  {
    if (!$multiple) {
      return false;
    }

    // Toggle the selection of the item
    in_array($position, $selectedPositions)
      ? $selectedPositions = array_values(array_diff($selectedPositions, [$position]))
      : $selectedPositions[] = $position;

    // We can clear the error
    $error = null;

    return false;
  }


  /**
   * @param list<int> &$selectedPositions
   */
  private function handleEnter(int $position, array &$selectedPositions, bool $multiple): bool
  {
    // For single selection always select the current item
    !$multiple && $selectedPositions = [$position];

    return true;
  }


  /**
   * @param list<string>|array<string, string> $options
   * @param list<int> $selectedPositions
   * @return string[]|string List returns value(s), map returns key(s)
   */
  private function extractResult(array $options, array $selectedPositions, bool $multiple): array|string
  {
    $values = array_is_list($options)
      ? $options
      : array_keys($options);

    $result = array_intersect_key($values, array_flip($selectedPositions));

    return $multiple
      ? $result
      : array_shift($result);
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
