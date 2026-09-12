<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\Parex\Parex;
use Lawondyss\Parex\Parser\ArgvParser;
use Lawondyss\Parex\Result\DynamicResult;
use Lawondyss\ParexCommander\Command;
use Lawondyss\ParexCommander\Exception\InvalidOptionException;
use Lawondyss\ParexCommander\IO;
use Lawondyss\ParexCommander\Type;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/TestIO.php';

/**
 * @testCase
 */
class CommandTest extends TestCase
{
  public function testCommandConfiguration(): void
  {
    $executed = false;
    $command = new Command(
      name: 'test',
      handler: function (DynamicResult $result, IO $io) use (&$executed): void {
        $executed = true;
      },
      description: 'Test command',
      version: '1.0.0',
    );

    Assert::same('test', $command->name);
    Assert::same('Test command', $command->description);
    Assert::same('1.0.0', $command->version);

    $command->addPositional(Type::string(), 'file', 'Path to file')
      ->addRequired(Type::integer(), 'count', 'c', 'Number of items')
      ->addOptional(Type::string(), 'output', 'o', 'Output path', default: 'out.txt')
      ->addFlag('verbose', 'v', 'Verbose mode');

    Assert::exception(function () use ($command) {
      $command->addFlag('verbose');
    }, InvalidOptionException::class, "Option 'verbose' already exists.");

    Assert::exception(function () use ($command) {
      $command->addPositional(Type::string(), 'file');
    }, InvalidOptionException::class, "Argument 'file' already exists.");
  }


  public function testCommandExecutionWithPositional(): void
  {
    $_SERVER['argv'] = ['app.php', 'test', 'input.txt', '--count=5', '--verbose'];
    array_shift($_SERVER['argv']); // removes app.php

    $calledResult = null;
    $command = new Command(
      name: 'test',
      handler: function (DynamicResult $result, IO $io) use (&$calledResult): void {
        $calledResult = $result;
      },
      description: 'Test execution',
      version: null,
    );

    $command->addPositional(Type::string(), 'file')
      ->addRequired(Type::integer(), 'count', 'c')
      ->addOptional(Type::string(), 'output', 'o', default: 'default.txt')
      ->addFlag('verbose', 'v');

    $parex = new Parex(new ArgvParser());
    $io = new TestIO();

    try {
      $command->run($parex, $io);
    } catch (TestExitException $e) {
      Assert::same(0, $e->getCode());
    }

    Assert::notNull($calledResult);
    Assert::same('input.txt', $calledResult->file);
    Assert::same(5, $calledResult->count);
    Assert::same('default.txt', $calledResult->output);
    Assert::true($calledResult->verbose);
  }


  public function testCommandHelpOption(): void
  {
    $_SERVER['argv'] = ['app.php', 'test', '--help'];
    array_shift($_SERVER['argv']);

    $command = new Command(
      name: 'test',
      handler: function (): void {},
      description: 'Help description',
      version: '2.0',
    );

    $parex = new Parex(new ArgvParser());
    $io = new TestIO();

    try {
      $command->run($parex, $io);
    } catch (TestExitException $e) {
      Assert::same(0, $e->getCode());
    }
  }
}

(new CommandTest())->run();
