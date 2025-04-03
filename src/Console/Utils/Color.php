<?php

namespace Lawondyss\ParexCommander\Console\Utils;

use function array_shift;
use function str_ends_with;
use function strlen;
use function substr;

/**
 * @method static string gray(string $s)
 * @method static string red(string $s)
 * @method static string green(string $s)
 * @method static string yellow(string $s)
 * @method static string blue(string $s)
 * @method static string magenta(string $s)
 * @method static string cyan(string $s)
 * @method static string white(string $s)
 *
 * @method static string grayDark(string $s)
 * @method static string redDark(string $s)
 * @method static string greenDark(string $s)
 * @method static string yellowDark(string $s)
 * @method static string blueDark(string $s)
 * @method static string magentaDark(string $s)
 * @method static string cyanDark(string $s)
 * @method static string whiteDark(string $s)
 *
 * @method static string grayBg(string $s)
 * @method static string redBg(string $s)
 * @method static string greenBg(string $s)
 * @method static string yellowBg(string $s)
 * @method static string blueBg(string $s)
 * @method static string magentaBg(string $s)
 * @method static string cyanBg(string $s)
 * @method static string whiteBg(string $s)
 *
 * @method static string grayDarkBg(string $s)
 * @method static string redDarkBg(string $s)
 * @method static string greenDarkBg(string $s)
 * @method static string yellowDarkBg(string $s)
 * @method static string blueDarkBg(string $s)
 * @method static string magentaDarkBg(string $s)
 * @method static string cyanDarkBg(string $s)
 * @method static string whiteDarkBg(string $s)
 */
final class Color
{
  private const Reset = Ansi::Esc . '[0m';
  private const Colors = [
    // Text colors (bright)
    'gray' => Ansi::Esc . '[1;30m',
    'red' => Ansi::Esc . '[1;31m',
    'green' => Ansi::Esc . '[1;32m',
    'yellow' => Ansi::Esc . '[1;33m',
    'blue' => Ansi::Esc . '[1;34m',
    'magenta' => Ansi::Esc . '[1;35m',
    'cyan' => Ansi::Esc . '[1;36m',
    'white' => Ansi::Esc . '[1;37m',
    // Text colors (dark)
    'grayDark' => Ansi::Esc . '[0;30m',
    'redDark' => Ansi::Esc . '[0;31m',
    'greenDark' => Ansi::Esc . '[0;32m',
    'yellowDark' => Ansi::Esc . '[0;33m',
    'blueDark' => Ansi::Esc . '[0;34m',
    'magentaDark' => Ansi::Esc . '[0;35m',
    'cyanDark' => Ansi::Esc . '[0;36m',
    'whiteDark' => Ansi::Esc . '[0;37m',
    // Background colors (bright)
    'grayBg' => Ansi::Esc . '[1;40m',
    'redBg' => Ansi::Esc . '[1;41m',
    'greenBg' => Ansi::Esc . '[1;42m',
    'yellowBg' => Ansi::Esc . '[1;43m',
    'blueBg' => Ansi::Esc . '[1;44m',
    'magentaBg' => Ansi::Esc . '[1;45m',
    'cyanBg' => Ansi::Esc . '[1;46m',
    'whiteBg' => Ansi::Esc . '[1;47m',
    // Background colors (dark)
    'grayDarkBg' => Ansi::Esc . '[0;40m',
    'redDarkBg' => Ansi::Esc . '[0;41m',
    'greenDarkBg' => Ansi::Esc . '[0;42m',
    'yellowDarkBg' => Ansi::Esc . '[0;43m',
    'blueDarkBg' => Ansi::Esc . '[0;44m',
    'magentaDarkBg' => Ansi::Esc . '[0;45m',
    'cyanDarkBg' => Ansi::Esc . '[0;46m',
    'whiteDarkBg' => Ansi::Esc . '[0;47m',
  ];


  private function __construct()
  {
  }


  public static function __callStatic(string $colorName, array $arguments): string
  {
    $s = array_shift($arguments) ?? '';

    if (str_ends_with($colorName, 'Bg')) {
      // Trim reset from the end of the string to preserve the background color
      if (str_ends_with($s, self::Reset)) {
        $s = substr($s, 0, strlen($s) - strlen(self::Reset));
      }

      // Inline padding for better readability
      $s = " {$s} ";
    }

    return self::Colors[$colorName] . $s . self::Reset;
  }
}
