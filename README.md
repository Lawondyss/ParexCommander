# Parex Commander

[![Latest Version](https://img.shields.io/packagist/v/lawondyss/parex-commander.svg?style=flat-square)](https://packagist.org/packages/lawondyss/parex-commander)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE)

Parex Commander is a lightweight PHP library designed to simplify the creation of interactive CLI applications. It
provides a structured and intuitive way to define commands, handle input/output, and manage the overall flow of your
application.

Built on top of the [Parex](https://packagist.org/packages/lawondyss/parex) library.

## Key Features

* **Command-Based Structure:** Organize your CLI application into logical commands, each with its own set of arguments
  and options.
* **Simplified Input/Output:** Easily handle user input and format output using the built-in IO system.
* **Extensible:** Easily extend the core functionality with your own custom commands and features.
* **Clean and Readable Code:** Designed with a focus on code clarity and maintainability.

## Installation

You can install Parex Commander via Composer:

```shell
bash composer require lawondyss/parex-commander
```

## Core Components

### `ParexCommander`

The `ParexCommander` class is the heart of your CLI application. It's responsible for:

* Registering and managing commands.
* Parsing user input and routing it to the appropriate command.
* Handling the overall execution flow.

**Example:**

```php
// Handlers
function day(DynamicResult $result, IO $io): void { $io->writeLn('☀️'); }
function night(DynamicResult $result, IO $io): void { $io->writeLn('🌙'); }

// Commands of Day & Night application
$commander = new ParexCommander('Day & Night', 'Something small and simple');
$commander->addCommand('day', day(...));
$commander->addCommand('night', night(...));

$commander->run();
```

See [example](examples/application.php) for more.

### `Command`

The `Command` class represents a single command within your application. It allows you to:

* Define the command's name, description, and version.
* Specify the arguments and options that the command accepts.
* Implement the command's logic in a handler function or invokable class.

**Example:**

```php
$commander->addCommand('migrate', migrate(...), 'Run missing migrations.')
          ->addOptional('id', help: 'Specifies a particular migration.', multiple: true)
          ->addFlag('dry-run', help: 'It simulates running the migration, but does not execute SQL.');
```

Or you can use the class on its own, see [example](examples/command.php) for more.

### `IO`

The `IO` class provides a simple and consistent way to interact with the user. It handles:

* Writing output to the console.
* Asking questions.
* Requires confirmation.
* Offers a selection of.

Better see [example](examples/io.php).

## Note

All examples are executable, just try them out 😉