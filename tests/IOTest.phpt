<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\ParexCommander\Console\Utils\Ansi;
use Lawondyss\ParexCommander\Console\Writer;
use Lawondyss\ParexCommander\IO;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/TestIO.php';

/**
 * @testCase
 */
class IOTest extends TestCase
{
  public function testWriteAndWriteLn(): void
  {
    $io = new IO();

    ob_start();
    $io->write('Hello', ' World');
    $out = ob_get_clean();
    Assert::same('Hello World', $out);

    ob_start();
    $io->writeLn('Header');
    $outLn = ob_get_clean();
    Assert::same("Header" . PHP_EOL, $outLn);
  }


  public function testWriteHeader(): void
  {
    $io = new IO();

    ob_start();
    $io->writeHeader('Test App');
    $out = ob_get_clean();

    Assert::contains('Test App', $out);
    Assert::contains('****', $out);
  }


  public function testClearScreen(): void
  {
    $io = new IO();

    ob_start();
    $io->clearScreen();
    $out = ob_get_clean();

    Assert::same(Ansi::ClearScreen . Ansi::CursorHome, $out);
  }


  public function testExitMethods(): void
  {
    $io = new TestIO();

    try {
      $io->exitSuccess();
    } catch (TestExitException $e) {
      Assert::same(0, $e->getCode());
    }

    try {
      $io->exitError(42);
    } catch (TestExitException $e) {
      Assert::same(42, $e->getCode());
    }
  }


  public function testMonitoring(): void
  {
    $io = new IO();

    ob_start();
    $res = $io->monitoring('Running task', function (Writer $writer): string {
      $writer->writeLn('Step 1');
      return 'done';
    }, lines: 2);
    $out = ob_get_clean();

    Assert::same('done', $res);
    Assert::contains(':> Running task', $out);
  }
}

(new IOTest())->run();
