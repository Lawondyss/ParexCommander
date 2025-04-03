<?php

namespace Lawondyss\ParexCommander\Console\Utils;

use function sprintf;

final class Ansi
{
  public const Esc = "\033";
  public const ClearDown = self::Esc . '[J';
  public const CursorStart = "\r";
  public const CursorUpFormat = self::Esc . '[%dA';
  public const CursorDownFormat = self::Esc . '[%dB';


  private function __construct()
  {
  }


  public static function cursorUpAndStart(int $lines = 1): string
  {
    return sprintf(self::CursorUpFormat, $lines) . self::CursorStart;
  }


  public static function cursorDownAndStart(int $lines = 1): string
  {
    return sprintf(self::CursorDownFormat, $lines) . self::CursorStart;
  }
}
