<?php

namespace Lawondyss\ParexCommander;

use DateTimeImmutable;

use function filter_var;
use function is_bool;
use function is_numeric;
use function str_replace;

use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_URL;

class Assert
{
  private function __construct()
  {
  }


  public static function assert(mixed $value, callable $check, string $error): true|string
  {
    if ($check($value)) {
      return true;
    }

    $value = match (true) {
      is_bool($value) => $value ? 'TRUE' : 'FALSE',
      is_numeric($value) => $value,
      default => "'{$value}'",
    };

    return str_replace('{$value}', $value, $error);
  }


  public static function boolean(mixed $value, string $error = 'Value {value} must be a boolean.'): true|string
  {
    return self::assert($value, is_bool(...), $error);
  }


  public static function number(mixed $value, string $error = 'Value {value} must be a number.'): true|string
  {
    return self::assert($value, is_numeric(...), $error);
  }


  public static function integer(mixed $value, string $error = 'Value {value} must be an integer.'): true|string
  {
    return self::assert($value, is_int(...), $error);
  }


  public static function dateFormat(string $value, string $format, string $error = 'Value {value} must be a valid date in format {format}.'): true|string
  {
    return self::assert(
      $value,
      static fn (mixed $val) => DateTimeImmutable::createFromFormat($format, $val),
      str_replace('{$format}', $format, $error),
    );
  }


  /**
   * @param list<mixed> $values
   */
  public static function contains(mixed $value, array $values, string $error = 'Value {value} must be from: {values}'): true|string
  {
    return self::assert(
      $value,
      check: static fn ($val) => in_array($value, $values, strict: true),
      error: str_replace('{$values}', implode(', ', $values), $error),
    );
  }


  public static function email(string $value, string $error = 'Value {value} must be a valid email.'): true|string
  {
    return self::assert(
      $value,
      check: static fn (string $val) => filter_var($val, FILTER_VALIDATE_EMAIL) !== false,
      error: $error,
    );
  }


  public static function url(string $value, string $error = 'Value {value} must be a valid URL.'): true|string
  {
    return self::assert(
      $value,
      check: static fn (string $val) => filter_var($val, FILTER_VALIDATE_URL) !== false,
      error: $error,
    );
  }


  public static function regexMatch(string $value, string $regex, string $error = 'Value {value} does not match the regex {regex}.'): true|string
  {
    return self::assert(
      $value,
      check: static fn (string $val) => preg_match($regex, $val) === false,
      error: str_replace('{$regex}', $regex, $error),
    );
  }
}
