<?php

namespace Lawondyss\ParexCommander;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Lawondyss\ParexCommander\Exception\InvalidValueException;
use SplFileInfo;

use function array_map;
use function file_exists;
use function is_dir;
use function round;

/**
 * @template T
 */
readonly class Type
{
  public const FlagName = 'flag';
  public const StringName = 'string';
  public const IntegerName = 'integer';
  public const NumberName = 'number';
  public const EnumName = 'enum';
  public const DateName = 'date';
  public const DateTimeName = 'dateTime';
  public const FileName = 'file';
  public const DirectoryName = 'directory';
  public const EmailName = 'email';
  public const UrlName = 'url';
  public const RegexName = 'regex';


  /**
   * @param string $name Identifier of the Type.
   * @param Closure|null $caster fn(console_input): mixed Changes input to type by Type.
   * @param Closure|null $validator fn(console_input): bool|string TRUE=valid, FALSE=invalid, string=own_error_message.
   * @param list<string>|null $values Allowed values for Type::enum.
   * @param string|null $dateTimeFormat Format for DateTimeImmutable::createFromFormat() for Type::date, Type::dateTime.
   * @param bool|null $mustExists Additional check for Type::file, Type::directory.
   * @param string|null $regex Regular expression for Type::regex.
   */
  protected function __construct(
    public string $name,
    public ?Closure $caster = null,
    public ?Closure $validator = null,
    public ?array $values = null,
    public ?string $dateTimeFormat = null,
    public ?bool $mustExists = null,
    public ?string $regex = null,
  ) {
  }


  /**
   * @return self<bool>
   */
  final public static function flag(): self
  {
    /** @var self<bool> $instance */
    $instance = new self(
      name: self::FlagName,
      validator: static fn ($val) => Assert::boolean($val, 'Value for flag must be a boolean.'),
    );

    return $instance;
  }


  /**
   * @return self<string>
   */
  public static function string(?Closure $validator = null): self
  {
    /** @var self<string> $instance */
    $instance = new self(
      name: self::StringName,
      validator: $validator,
    );

    return $instance;
  }


  /**
   * @return self<int>
   */
  public static function integer(?Closure $validator = null): self
  {
    /** @var self<int> $instance */
    $instance = new self(
      name: self::IntegerName,
      caster: static fn (string $val) => (int)round((float)$val),
      validator: $validator ?? Assert::integer(...),
    );

    return $instance;
  }


  /**
   * @return self<int|float>
   */
  public static function number(?Closure $validator = null): self
  {
    /** @var self<int|float> $instance */
    $instance = new self(
      name: self::NumberName,
      caster: static fn (string $val) => (is_numeric($val) ? $val + 0 : 0),
      validator: $validator ?? Assert::number(...),
    );

    return $instance;
  }


  /**
   * @param list<string> $values
   * @return self<string>
   */
  public static function enum(array $values, ?Closure $validator = null): self
  {
    $values = array_map(static fn ($val) => (string)$val, $values);

    /** @var self<string> $instance */
    $instance = new self(
      name: self::EnumName,
      validator: $validator ?? static fn (string $val) => Assert::contains($val, $values),
      values: $values,
    );

    return $instance;
  }


  /**
   * @return self<DateTimeImmutable>
   */
  public static function date(string $format = 'Y-m-d', ?DateTimeZone $timeZone = null, ?Closure $validator = null): self
  {
    /** @var self<DateTimeImmutable> $instance */
    $instance = new self(
      name: self::DateName,
      caster: static fn (string $val) => DateTimeImmutable::createFromFormat($format, $val, $timeZone),
      validator: $validator ?? static fn (string $val) => Assert::dateFormat($val, $format),
      dateTimeFormat: $format,
    );

    return $instance;
  }


  /**
   * @return self<DateTimeImmutable>
   */
  public static function dateTime(string $format = 'Y-m-d H:i', ?DateTimeZone $timeZone = null, ?Closure $validator = null): self
  {
    /** @var self<DateTimeImmutable> $instance */
    $instance = new self(
      name: self::DateTimeName,
      caster: static fn (string $val) => DateTimeImmutable::createFromFormat($format, $val, $timeZone),
      validator: $validator ?? static fn (string $val) => Assert::dateFormat($val, $format),
      dateTimeFormat: $format,
    );

    return $instance;
  }


  /**
   * @return self<SplFileInfo>
   */
  public static function file(bool $mustExists = true, ?Closure $validator = null): self
  {
    /** @var self<SplFileInfo> $instance */
    $instance = new self(
      name: self::FileName,
      caster: static fn (string $val) => new SplFileInfo($val),
      validator: $validator ?? static fn (string $val) => ($mustExists && !file_exists($val))
      ? "File '$val' must exist."
      : true,
      mustExists: $mustExists,
    );

    return $instance;
  }


  /**
   * @return self<SplFileInfo>
   */
  public static function directory(bool $mustExists = true, ?Closure $validator = null): self
  {
    /** @var self<SplFileInfo> $instance */
    $instance = new self(
      name: self::DirectoryName,
      caster: static fn (string $val) => new SplFileInfo($val),
      validator: $validator ?? static fn (string $val) => ($mustExists && !is_dir($val))
      ? "Directory '$val' must exist."
      : true,
      mustExists: $mustExists,
    );

    return $instance;
  }


  /**
   * @return self<string>
   */
  public static function email(?Closure $validator = null): self
  {
    /** @var self<string> $instance */
    $instance = new self(
      name: self::EmailName,
      validator: $validator ?? Assert::email(...),
    );

    return $instance;
  }


  /**
   * @return self<string>
   */
  public static function url(?Closure $validator = null): self
  {
    /** @var self<string> $instance */
    $instance = new self(
      name: self::UrlName,
      validator: $validator ?? Assert::url(...),
    );

    return $instance;
  }


  /**
   * @return self<string>
   */
  public static function regex(string $regex, ?Closure $validator = null): self
  {
    /** @var self<string> $instance */
    $instance = new self(
      name: self::RegexName,
      validator: $validator ?? static fn (string $val) => Assert::regexMatch($val, $regex),
      regex: $regex,
    );

    return $instance;
  }


  /**
   * @return T|null
   * @throws InvalidValueException
   */
  public function cast(mixed $input): mixed
  {
    if ($input === null) {
      return null;
    }

    if (isset($this->validator)) {
      $validationResult = ($this->validator)($input);

      ($validationResult === true) || throw new InvalidValueException(
        message: is_string($validationResult)
          ? $validationResult
          : "Value '" . (is_scalar($input) ? (string) $input : gettype($input)) . "' is not valid for type '{$this->name}'.",
      );
    }

    /** @var T|null */
    return isset($this->caster)
      ? ($this->caster)($input)
      : $input;
  }
}
