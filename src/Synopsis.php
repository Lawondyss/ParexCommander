<?php

namespace Lawondyss\ParexCommander;

use function implode;
use function is_int;
use function str_pad;
use function str_repeat;
use function strtoupper;
use function ucfirst;

use const PHP_EOL;

readonly class Synopsis
{
  public function __construct(
    public Type $type,
    public string $name,
    public ?string $short,
    public string $help,
    public bool $multiple = false,
    public mixed $default = null,
    public bool $required = false,
    public ?int $position = null,
  ) {
  }


  public function presentation(): string
  {
    return match (true) {
      $this->isPositional() => $this->required ? "<{$this->synapse()}>" : "[<{$this->synapse()}>]",
      $this->isRequired() => "{$this->synapse()}={$this->type->name}",
      $this->isFlag() => "[{$this->synapse()}]",
      default => "[{$this->synapse()}={$this->type->name}]",
    };
  }


  public function description(): string
  {
    $leftSideWidth = 25;
    $indent = str_repeat(' ', $leftSideWidth);
    $desc = [];

    $desc[] = str_pad("  {$this->synapse()}", length: $leftSideWidth) . $this->help;

    if (isset($this->default)) {
      $desc[] = "{$indent}Default value: {$this->default}";
    }

    if ($this->multiple) {
      $desc[] = "{$indent}Can be call multiple times.";
    }

    if (isset($this->type->values)) {
      $desc[] = "{$indent}Allowed values: " . implode(', ', $this->type->values);
    }

    if (isset($this->type->regex)) {
      $desc[] = "{$indent}Regex: {$this->type->regex}";
    }

    if (isset($this->type->dateTimeFormat)) {
      $what = ucfirst($this->type->name);
      $desc[] = "{$indent}{$what} format: {$this->type->dateTimeFormat}";
    }

    if ($this->type->mustExists === true) {
      $what = ucfirst($this->type->name);
      $desc[] = "{$indent}{$what} must exist.";
    }

    return implode(PHP_EOL, $desc);
  }


  public function isPositional(): bool
  {
    return is_int($this->position);
  }


  public function isRequired(): bool
  {
    return $this->position === null && $this->required;
  }


  public function isFlag(): bool
  {
    return $this->type->name === Type::FlagName;
  }


  protected function synapse(): string
  {
    return match (true) {
      $this->isPositional() => strtoupper($this->name),
      $this->isFlag() => "--{$this->name}" . ($this->short ? "/-{$this->short}" : ''),
      default => "--{$this->name}" . ($this->short ? "(-{$this->short})" : ''),
    };
  }
}
