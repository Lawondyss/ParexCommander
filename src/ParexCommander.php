<?php

namespace Lawondyss\ParexCommander;

use Lawondyss\Parex\Parex;
use Lawondyss\Parex\Parser\ArgvParser;
use Lawondyss\ParexCommander\Exception\InvalidOptionException;

use function array_shift;
use function str_pad;

class ParexCommander
{
  /** @var array<string, Synopsis> $synopses */
  protected array $synopses = [];

  /** @var array<string, Command> $commands */
  private array $commands = [];


  public function __construct(
    public readonly string $name,
    public readonly string $description,
    public readonly ?string $version = null,
    public readonly IO $io = new IO(),
  ) {
  }


  public function addCommand(string $name, callable $handler, string $description = '', ?string $version = null): Command
  {
    return $this->commands[$name] = new Command($name, $handler(...), $description, $version);
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


  public function run(): never
  {
    $this->showHeader();

    // First is always the script name.
    // The ArgvParser also uses array_shift($_SERVER['argv']) to remove the script name.
    // But instead it removes $commandName, and thus it will not be in DynamicResult::$POSITIONAL[0].
    array_shift($_SERVER['argv']);

    $commandName = $_SERVER['argv'][0] ?? null;

    if ($commandName === null) {
      $this->showHelp();
      $this->io->exitSuccess();
    }

    if (!isset($this->commands[$commandName])) {
      $this->io->writeLn("[Error] Unknown command '$commandName'");
      $this->showHelp();
      $this->io->exitError();
    }

    $command = $this->commands[$commandName];

    // Propagate shared options to the command.
    foreach ($this->synopses as $synopsis) {
      try {
        $synopsis->isFlag()
          ? $command->addFlag($synopsis->name, $synopsis->short, $synopsis->help)
          : $command->addOptional($synopsis->type, $synopsis->name, $synopsis->short, $synopsis->help, $synopsis->default, $synopsis->multiple);
      } catch (InvalidOptionException) {
        // Ignoring exception. It is only for duplicate name, so the command defines its own option.
      }
    }

    $command->run($this->createParex(), $this->io);
  }


  public function showHeader(): void
  {
    $this->io->writeHeader($this->name);
    $this->io->writeLn($this->description);
    isset($this->version) && $this->io->writeLn("Version: {$this->version}");
    $this->io->writeLn();
  }


  public function showHelp(): void
  {
    $leftSideWidth = 25;

    $this->io->writeLn("Usage: <command> [options]");
    $this->io->writeLn();

    $this->commands === []
      ? $this->io->writeLn('No commands available.')
      : $this->io->writeLn('Commands:');

    foreach ($this->commands as $name => $command) {
      $this->io->writeLn(str_pad("  {$name}", length: $leftSideWidth), $command->description);
    }

    $this->io->writeLn();
    $this->io->writeLn("For help with a specific command, use: <command> --help/-h");
  }


  protected function addSynopsis(Synopsis $synopsis): static
  {
    $name = $synopsis->name;

    isset($this->synopses[$name]) && throw new InvalidOptionException("Option '{$name}' already exists.");

    $this->synopses[$name] = $synopsis;

    return $this;
  }


  protected function createParex(): Parex
  {
    return new Parex(new ArgvParser());
  }
}
