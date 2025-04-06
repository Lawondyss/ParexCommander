<?php

namespace Lawondyss\ParexCommander;

use Closure;
use Lawondyss\Parex\Parex;
use Lawondyss\Parex\ParexException;
use Lawondyss\Parex\Result\DynamicResult;
use Lawondyss\ParexCommander\Exception\InvalidOptionException;
use Lawondyss\ParexCommander\Exception\MissingException;
use Lawondyss\ParexCommander\Exception\ParexCommanderException;

use function implode;

use const PHP_EOL;

class Command
{
  /** @var array<string, Synopsis> $synopses */
  protected array $synopses = [];

  protected int $lastPosition = 0;


  /**
   * @param string $name Identifier for calling the command.
   * @param Closure $handler fn(DynamicResult, IO): never Handler gets a typed input and IO to communicate with user.
   * @param string $description A short description of what the command does.
   * @param string|null $version For display only, you cannot have multiple versions of the same command.
   */
  public function __construct(
    public readonly string $name,
    public readonly Closure $handler,
    public readonly string $description,
    public readonly ?string $version,
  ) {
    $this->addFlag('help', 'h', 'Show this help.');
  }


  public function addPositional(Type $type, string $name, string $help = '', bool $required = true): static
  {
    return $this->addSynopsis(new Synopsis(
      $type,
      $name,
      short: null,
      help: $help,
      required: $required,
      position: $this->lastPosition++,
    ));
  }


  public function addRequired(
    Type $type,
    string $name,
    ?string $short = null,
    string $help = '',
    bool $multiple = false,
  ): self {
    return $this->addSynopsis(new Synopsis($type, $name, $short, $help, $multiple, required: true));
  }


  public function addOptional(
    Type $type,
    string $name,
    ?string $short = null,
    string $help = '',
    mixed $default = null,
    bool $multiple = false,
  ): self {
    return $this->addSynopsis(new Synopsis($type, $name, $short, $help, $multiple, $default));
  }


  public function addFlag(string $name, ?string $short = null, string $help = ''): self
  {
    return $this->addSynopsis(new Synopsis(Type::flag(), $name, $short, $help));
  }


  public function run(Parex $parex, IO $io): never
  {
    try {
      $values = $this->initParex($parex)->parse();

      // @phpstan-ignore property.notFound
      if ($values->help) {
        $io->writeLn($this->createHelp());
        $io->exitSuccess();
      }

      $this->checkPositional($values);

      // Cast data for pseudo-typed DynamicResult
      $casted = [];

      foreach ($this->synopses as $synopsis) {
        $name = $synopsis->name;
        $value = $synopsis->isPositional()
          ? ($values->POSITIONAL[$synopsis->position] ?? null)
          : $values->{$name};
        $casted[$name] = $synopsis->type->cast($value) ?? $synopsis->default;
      }

      // The handler should call exit() itself
      ($this->handler)(new DynamicResult(...$casted), $io);
      // But just in case
      $io->exitSuccess();

    } catch (ParexException|ParexCommanderException $exc) {
      $io->writeLn('[ERROR] ', $exc->getMessage());
      $io->writeLn();
      $io->writeLn($this->createHelp());
      $io->exitError();
    }
  }


  protected function createHelp(): string
  {
    // Split by type for better sorting in the help
    $arguments = $requires = $optionals = $flags = [];

    foreach ($this->synopses as $synopsis) {
      match (true) {
        $synopsis->isPositional() => $arguments[] = $synopsis,
        $synopsis->isFlag() => $flags[] = $synopsis,
        $synopsis->isRequired() => $requires[] = $synopsis,
        default => $optionals[] = $synopsis,
      };
    }

    $usage = ["Usage: {$this->name}"];
    $descriptions = [];

    $arguments !== [] && $descriptions[] = 'Arguments:';

    foreach ($arguments as $synopsis) {
      $usage[] = $synopsis->presentation();
      $descriptions[] = $synopsis->description();
    }

    $arguments !== [] && $descriptions[] = '';

    /** @var Synopsis[] $options */
    $options = [...$requires, ...$optionals, ...$flags];
    $options !== [] && $descriptions[] = 'Options:';

    foreach ($options as $synopsis) {
      $usage[] = $synopsis->presentation();
      $descriptions[] = $synopsis->description();
    }

    return implode(PHP_EOL, [
      'Command ' . $this->name . (isset($this->version) ? " v{$this->version}" : ''),
      $this->description,
      '',
      implode(' ', $usage),
      '',
      ...$descriptions,
    ]);
  }


  protected function addSynopsis(Synopsis $synopsis): static
  {
    $name = $synopsis->name;

    isset($this->synopses[$name]) && throw new InvalidOptionException(
      message: ($synopsis->isPositional() ? 'Argument' : 'Option') . " '{$name}' already exists.",
    );

    $this->synopses[$name] = $synopsis;

    return $this;
  }


  protected function initParex(Parex $parex): Parex
  {
    foreach ($this->synopses as $synopsis) {
      match (true) {
        $synopsis->isPositional() => null, // Nothing, because Parex can't define a positional, check in command
        $synopsis->isRequired() => $parex->addRequire($synopsis->name, $synopsis->short, $synopsis->multiple),
        $synopsis->isFlag() => $parex->addFlag($synopsis->name, $synopsis->short),
        default => $parex->addOptional($synopsis->name, $synopsis->short, $synopsis->default, $synopsis->multiple),
      };
    }

    return $parex;
  }


  /**
   * @throws MissingException
   */
  protected function checkPositional(DynamicResult $result): void
  {
    foreach ($this->synopses as $synopsis) {
      if ($synopsis->isPositional() && $synopsis->required && !isset($result->POSITIONAL[$synopsis->position])) {
        throw new MissingException("Argument '{$synopsis->name}' is required.", $synopsis);
      }
    }
  }
}
