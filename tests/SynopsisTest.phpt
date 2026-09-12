<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\ParexCommander\Synopsis;
use Lawondyss\ParexCommander\Type;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class SynopsisTest extends TestCase
{
  public function testPositionalSynopsis(): void
  {
    $synopsis = new Synopsis(
      type: Type::string(),
      name: 'filename',
      short: null,
      help: 'File path',
      position: 1,
      required: true,
    );

    Assert::true($synopsis->isPositional());
    Assert::false($synopsis->isRequired());
    Assert::false($synopsis->isFlag());
    Assert::same('<FILENAME>', $synopsis->presentation());
    Assert::contains('File path', $synopsis->description());

    $optionalPositional = new Synopsis(
      type: Type::string(),
      name: 'output',
      short: null,
      help: 'Output file',
      position: 2,
      required: false,
    );
    Assert::same('[<OUTPUT>]', $optionalPositional->presentation());
  }


  public function testFlagSynopsis(): void
  {
    $synopsis = new Synopsis(
      type: Type::flag(),
      name: 'verbose',
      short: 'v',
      help: 'Verbose output',
    );

    Assert::false($synopsis->isPositional());
    Assert::false($synopsis->isRequired());
    Assert::true($synopsis->isFlag());
    Assert::same('[--verbose/-v]', $synopsis->presentation());
    Assert::contains('Verbose output', $synopsis->description());
  }


  public function testOptionSynopsis(): void
  {
    $synopsis = new Synopsis(
      type: Type::string(),
      name: 'config',
      short: 'c',
      help: 'Configuration file',
      required: true,
    );

    Assert::false($synopsis->isPositional());
    Assert::true($synopsis->isRequired());
    Assert::false($synopsis->isFlag());
    Assert::same('--config(-c)=string', $synopsis->presentation());

    $optionalSynopsis = new Synopsis(
      type: Type::integer(),
      name: 'limit',
      short: 'l',
      help: 'Limit items',
      default: 10,
    );
    Assert::same('[--limit(-l)=integer]', $optionalSynopsis->presentation());

    $description = $optionalSynopsis->description();
    Assert::contains('Default value: 10', $description);
  }


  public function testDescriptionDetails(): void
  {
    $enumType = Type::enum(['low', 'high']);
    $synopsis = new Synopsis(
      type: $enumType,
      name: 'level',
      short: null,
      help: 'Set priority level',
      multiple: true,
    );

    $desc = $synopsis->description();
    Assert::contains('Can be call multiple times.', $desc);
    Assert::contains('Allowed values: low, high', $desc);

    $fileType = Type::file(mustExists: true);
    $synopsisFile = new Synopsis(
      type: $fileType,
      name: 'input',
      short: null,
      help: 'Input file',
    );
    Assert::contains('File must exist.', $synopsisFile->description());
  }
}

(new SynopsisTest())->run();
