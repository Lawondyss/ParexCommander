<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\Parex\Result\DynamicResult;
use Lawondyss\ParexCommander\IO;
use Lawondyss\ParexCommander\ParexCommander;
use Lawondyss\ParexCommander\Type;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/TestIO.php';

/**
 * @testCase
 */
class ParexCommanderTest extends TestCase
{
  public function testCommanderRegistration(): void
  {
    $io = new TestIO();
    $commander = new ParexCommander('App Title', 'App Description', '1.0.0', $io);

    Assert::same('App Title', $commander->name);
    Assert::same('App Description', $commander->description);
    Assert::same('1.0.0', $commander->version);

    $command = $commander->addCommand('greet', function (): void {}, 'Greets user');
    Assert::same('greet', $command->name);
  }


  public function testRunWithoutCommandShowsHelp(): void
  {
    $_SERVER['argv'] = ['app.php'];

    $io = new TestIO();
    $commander = new ParexCommander('App', 'Desc', '1.0', $io);

    try {
      $commander->run();
    } catch (TestExitException $e) {
      Assert::same(0, $e->getCode());
    }
  }


  public function testRunUnknownCommand(): void
  {
    $_SERVER['argv'] = ['app.php', 'unknown'];

    $io = new TestIO();
    $commander = new ParexCommander('App', 'Desc', '1.0', $io);

    try {
      $commander->run();
    } catch (TestExitException $e) {
      Assert::same(1, $e->getCode());
    }
  }


  public function testRunValidCommandWithSharedOption(): void
  {
    $_SERVER['argv'] = ['app.php', 'say', '--debug'];

    $calledResult = null;
    $io = new TestIO();
    $commander = new ParexCommander('App', 'Desc', '1.0', $io);

    $commander->addFlag('debug', 'd', 'Debug mode');

    $commander->addCommand('say', function (DynamicResult $result) use (&$calledResult): void {
      $calledResult = $result;
    }, 'Say something');

    try {
      $commander->run();
    } catch (TestExitException $e) {
      Assert::same(0, $e->getCode());
    }

    Assert::notNull($calledResult);
    Assert::true($calledResult->debug);
  }
}

(new ParexCommanderTest())->run();
