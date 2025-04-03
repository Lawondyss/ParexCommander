<?php

namespace Lawondyss\ParexCommander\Console\Utils;

final class Key
{
  public const ArrowUp = Ansi::Esc . '[A';
  public const ArrowDown = Ansi::Esc . '[B';
  public const Space = ' ';
  public const Enter1 = "\n";
  public const Enter2 = "\r";


  private function __construct()
  {
  }
}
