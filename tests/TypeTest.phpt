<?php

declare(strict_types=1);

namespace Lawondyss\ParexCommander\Tests;

use DateTimeImmutable;
use Lawondyss\ParexCommander\Exception\InvalidValueException;
use Lawondyss\ParexCommander\Type;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
class TypeTest extends TestCase
{
  public function testFlag(): void
  {
    $type = Type::flag();
    Assert::same(Type::FlagName, $type->name);
    Assert::null($type->cast(null));
    Assert::true($type->cast(true));
    Assert::false($type->cast(false));

    Assert::exception(function () use ($type) {
      $type->cast('not-a-bool');
    }, InvalidValueException::class);
  }


  public function testString(): void
  {
    $type = Type::string();
    Assert::same(Type::StringName, $type->name);
    Assert::same('hello', $type->cast('hello'));

    $customType = Type::string(static fn ($val) => $val === 'valid' ? true : 'Invalid string value.');
    Assert::same('valid', $customType->cast('valid'));
    Assert::exception(function () use ($customType) {
      $customType->cast('invalid');
    }, InvalidValueException::class, 'Invalid string value.');
  }


  public function testInteger(): void
  {
    $type = Type::integer();
    Assert::same(Type::IntegerName, $type->name);
    Assert::same(42, $type->cast(42));

    Assert::exception(function () use ($type) {
      $type->cast('not-int');
    }, InvalidValueException::class);
  }


  public function testNumber(): void
  {
    $type = Type::number();
    Assert::same(Type::NumberName, $type->name);
    Assert::same(42, $type->cast('42'));
    Assert::same(3.14, $type->cast('3.14'));

    Assert::exception(function () use ($type) {
      $type->cast('not-number');
    }, InvalidValueException::class);
  }


  public function testEnum(): void
  {
    $type = Type::enum(['foo', 'bar']);
    Assert::same(Type::EnumName, $type->name);
    Assert::same(['foo', 'bar'], $type->values);
    Assert::same('foo', $type->cast('foo'));

    Assert::exception(function () use ($type) {
      $type->cast('baz');
    }, InvalidValueException::class);
  }


  public function testDateAndDateTime(): void
  {
    $dateType = Type::date('Y-m-d');
    Assert::same(Type::DateName, $dateType->name);
    /** @var DateTimeImmutable $dt */
    $dt = $dateType->cast('2025-05-10');
    Assert::type(DateTimeImmutable::class, $dt);
    Assert::same('2025-05-10', $dt->format('Y-m-d'));

    Assert::exception(function () use ($dateType) {
      $dateType->cast('invalid-date');
    }, InvalidValueException::class);

    $dateTimeType = Type::dateTime('Y-m-d H:i');
    Assert::same(Type::DateTimeName, $dateTimeType->name);
    /** @var DateTimeImmutable $dtm */
    $dtm = $dateTimeType->cast('2025-05-10 14:30');
    Assert::type(DateTimeImmutable::class, $dtm);
    Assert::same('2025-05-10 14:30', $dtm->format('Y-m-d H:i'));
  }


  public function testFileAndDirectory(): void
  {
    $fileType = Type::file(mustExists: true);
    Assert::same(Type::FileName, $fileType->name);
    Assert::true($fileType->mustExists);

    $file = $fileType->cast(__FILE__);
    Assert::type(SplFileInfo::class, $file);

    Assert::exception(function () use ($fileType) {
      $fileType->cast('/non/existent/file.txt');
    }, InvalidValueException::class);

    $noMustExistFile = Type::file(mustExists: false);
    $file2 = $noMustExistFile->cast('/non/existent/file.txt');
    Assert::type(SplFileInfo::class, $file2);

    $dirType = Type::directory(mustExists: true);
    Assert::same(Type::DirectoryName, $dirType->name);
    $dir = $dirType->cast(__DIR__);
    Assert::type(SplFileInfo::class, $dir);

    Assert::exception(function () use ($dirType) {
      $dirType->cast(__FILE__);
    }, InvalidValueException::class);
  }


  public function testEmailUrlRegex(): void
  {
    $emailType = Type::email();
    Assert::same('test@example.com', $emailType->cast('test@example.com'));
    Assert::exception(function () use ($emailType) {
      $emailType->cast('invalid-email');
    }, InvalidValueException::class);

    $urlType = Type::url();
    Assert::same('https://example.com', $urlType->cast('https://example.com'));
    Assert::exception(function () use ($urlType) {
      $urlType->cast('invalid-url');
    }, InvalidValueException::class);

    $regexType = Type::regex('/^[a-z]+$/');
    Assert::same('abc', $regexType->cast('abc'));
    Assert::exception(function () use ($regexType) {
      $regexType->cast('123');
    }, InvalidValueException::class);
  }
}

(new TypeTest())->run();
