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
   * @return static<bool>
   */
  final public static function flag(): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::FlagName,
      validator: static fn ($val) => Assert::boolean($val, 'Value for flag must be a boolean.'),
    );
  }


  /**
   * @return static<string>
   */
  public static function string(?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::StringName,
      validator: $validator,
    );
  }


  /**
   * @return static<int>
   */
  public static function integer(?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::IntegerName,
      caster: static fn (string $val) => (int)round((float)$val),
      validator: $validator ?? Assert::integer(...),
    );
  }


  /**
   * @return static<int|float>
   */
  public static function number(?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::NumberName,
      caster: static fn (string $val) => (1 * $val), // @phpstan-ignore binaryOp.invalid
      validator: $validator ?? Assert::number(...),
    );
  }


  /**
   * @param list<string> $values
   * @return static<string>
   */
  public static function enum(array $values, ?Closure $validator = null): static
  {
    $values = array_map(static fn ($val) => (string)$val, $values);

    // @phpstan-ignore new.static
    return new static(
      name: static::EnumName,
      validator: $validator ?? static fn (string $val) => Assert::contains($val, $values),
      values: $values,
    );
  }


  /**
   * @return static<DateTimeImmutable>
   */
  public static function date(string $format = 'Y-m-d', ?DateTimeZone $timeZone = null, ?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::DateName,
      caster: static fn (string $val) => DateTimeImmutable::createFromFormat($format, $val, $timeZone),
      validator: $validator ?? static fn (string $val) => Assert::dateFormat($val, $format),
      dateTimeFormat: $format,
    );
  }


  /**
   * @return static<DateTimeImmutable>
   */
  public static function dateTime(string $format = 'Y-m-d H:i', ?DateTimeZone $timeZone = null, ?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::DateTimeName,
      caster: static fn (string $val) => DateTimeImmutable::createFromFormat($format, $val, $timeZone),
      validator: $validator ?? static fn (string $val) => Assert::dateFormat($val, $format),
      dateTimeFormat: $format,
    );
  }


  /**
   * @return static<SplFileInfo>
   */
  public static function file(bool $mustExists = true, ?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::FileName,
      caster: static fn (string $val) => new SplFileInfo($val),
      validator: $validator ?? static fn (string $val) => ($mustExists && !file_exists($val))
      ? "File '$val' must exist."
      : true,
      mustExists: $mustExists,
    );
  }


  /**
   * @return static<SplFileInfo>
   */
  public static function directory(bool $mustExists = true, ?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::DirectoryName,
      caster: static fn (string $val) => new SplFileInfo($val),
      validator: $validator ?? static fn (string $val) => ($mustExists && !is_dir($val))
      ? "Directory '$val' must exist."
      : true,
      mustExists: $mustExists,
    );
  }


  /**
   * @return static<string>
   */
  public static function email(?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::EmailName,
      validator: $validator ?? Assert::email(...),
    );
  }


  /**
   * @return static<string>
   */
  public static function url(?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::UrlName,
      validator: $validator ?? Assert::url(...),
    );
  }


  /**
   * @return static<string>
   */
  public static function regex(string $regex, ?Closure $validator = null): static
  {
    // @phpstan-ignore new.static
    return new static(
      name: static::RegexName,
      validator: $validator ?? static fn (string $val) => Assert::regexMatch($val, $regex),
      regex: $regex,
    );
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
          : "Value '$input' is not valid for type '{$this->name}'.",
      );
    }

    return isset($this->caster)
      ? ($this->caster)($input)
      : $input;
  }
}
