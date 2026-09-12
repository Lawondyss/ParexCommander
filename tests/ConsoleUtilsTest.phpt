<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Lawondyss\ParexCommander\Console\Utils\Color;
use Lawondyss\ParexCommander\Console\Utils\Key;
use Lawondyss\ParexCommander\Console\Writer;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class ConsoleUtilsTest extends TestCase
{
  public function testAnsi(): void
  {
    Assert::same("\033[2A\r", Ansi::cursorUpAndStart(2));
    Assert::same("\033[3B\r", Ansi::cursorDownAndStart(3));
  }


  public function testColor(): void
  {
    $red = Color::red('text');
    Assert::same("\033[1;31mtext\033[0m", $red);

    $redBg = Color::redBg('text');
    Assert::same("\033[1;41m text \033[0m", $redBg);
  }


  public function testKeyConstants(): void
  {
    Assert::same("\033[A", Key::ArrowUp);
    Assert::same("\033[B", Key::ArrowDown);
    Assert::same(' ', Key::Space);
  }


  public function testWriter(): void
  {
    $writer = new Writer();

    ob_start();
    $writer->write('Hello ', 'World');
    $output = ob_get_clean();
    Assert::same('Hello World', $output);

    ob_start();
    $writer->writeLn('Hello');
    $outputLn = ob_get_clean();
    Assert::same("Hello" . PHP_EOL, $outputLn);
  }
}

(new ConsoleUtilsTest())->run();
