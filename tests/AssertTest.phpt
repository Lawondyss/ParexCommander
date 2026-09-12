<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use Lawondyss\ParexCommander\Assert;
use Tester\Assert as TesterAssert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class AssertTest extends TestCase
{
  public function testBoolean(): void
  {
    TesterAssert::true(Assert::boolean(true));
    TesterAssert::true(Assert::boolean(false));
    TesterAssert::same("Value 'string' must be a boolean.", Assert::boolean('string'));
    TesterAssert::same('Custom error', Assert::boolean('string', 'Custom error'));
  }


  public function testNumber(): void
  {
    TesterAssert::true(Assert::number(123));
    TesterAssert::true(Assert::number(12.34));
    TesterAssert::true(Assert::number('123'));
    TesterAssert::same("Value 'abc' must be a number.", Assert::number('abc'));
  }


  public function testInteger(): void
  {
    TesterAssert::true(Assert::integer(123));
    TesterAssert::true(Assert::integer('123'));
    TesterAssert::same('Value 12.34 must be an integer.', Assert::integer(12.34));
    TesterAssert::same("Value 'abc' must be an integer.", Assert::integer('abc'));
  }


  public function testDateFormat(): void
  {
    TesterAssert::true(Assert::dateFormat('2025-01-01', 'Y-m-d'));
    TesterAssert::same("Value 'invalid' must be a valid date in format Y-m-d.", Assert::dateFormat('invalid', 'Y-m-d'));
  }


  public function testContains(): void
  {
    TesterAssert::true(Assert::contains('a', ['a', 'b']));
    TesterAssert::same("Value 'c' must be from: a, b", Assert::contains('c', ['a', 'b']));
  }


  public function testEmail(): void
  {
    TesterAssert::true(Assert::email('test@example.com'));
    TesterAssert::same("Value 'invalid' must be a valid email.", Assert::email('invalid'));
  }


  public function testUrl(): void
  {
    TesterAssert::true(Assert::url('https://example.com'));
    TesterAssert::same("Value 'invalid' must be a valid URL.", Assert::url('invalid'));
  }


  public function testRegexMatch(): void
  {
    TesterAssert::true(Assert::regexMatch('123', '/[0-9]+/'));
    TesterAssert::same("Value 'abc' does not match the regex /[0-9]+/.", Assert::regexMatch('abc', '/[0-9]+/'));
  }
}

(new AssertTest())->run();
