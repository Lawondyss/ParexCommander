<?php

namespace Lawondyss\ParexCommander\Console;

use function fgets;
use function is_string;
use function trim;

use const STDIN;

class Question
{
  public function make(
    string $prompt,
    string $default = '',
    ?callable $validator = null,
  ): string {
    while (true) {
      $displayPrompt = $prompt;
      $default && $displayPrompt .= " [{$default}]";
      echo $displayPrompt, ' ';

      $text = trim(fgets(STDIN));
      $text = $text !== '' ? $text : $default;

      if ($validator !== null) {
        $result = $validator($text);

        if ($result !== true) {
          $error = is_string($result) ? $result : 'Invalid value';
          echo $error, PHP_EOL;
          continue;
        }
      }

      return $text;
    }
  }
}
