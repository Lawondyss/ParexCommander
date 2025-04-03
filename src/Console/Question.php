<?php

namespace Lawondyss\ParexCommander\Console;

use function fgets;
use function is_string;
use function trim;

use const STDIN;

class Question
{
  public function __construct(
    private readonly Writer $writer,
  ) {
  }


  public function make(
    string $prompt,
    string $default = '',
    ?callable $validator = null,
  ): string {
    while (true) {
      $displayPrompt = $prompt;
      $default && $displayPrompt .= " [{$default}]";
      $this->writer->write($displayPrompt, $default ? " [{$default}]" : '', ' ');

      $text = trim(fgets(STDIN));
      $text = $text !== '' ? $text : $default;

      if ($validator !== null) {
        $result = $validator($text);

        if ($result !== true) {
          $this->writer->writeLn(is_string($result) ? $result : 'Invalid value');
          continue;
        }
      }

      return $text;
    }
  }
}
