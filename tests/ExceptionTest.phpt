<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\ParexCommander\Exception\MissingException;
use Lawondyss\ParexCommander\Exception\ParexCommanderException;
use Lawondyss\ParexCommander\Synopsis;
use Lawondyss\ParexCommander\Type;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class ExceptionTest extends TestCase
{
  public function testMissingException(): void
  {
    $synopsis = new Synopsis(Type::string(), 'name', null, 'Help');
    $exc = new MissingException('Missing argument', $synopsis);

    Assert::type(ParexCommanderException::class, $exc);
    Assert::same('Missing argument', $exc->getMessage());
    Assert::same($synopsis, $exc->synopsis);
  }
}

(new ExceptionTest())->run();
