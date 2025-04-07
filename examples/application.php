<?php

use Lawondyss\Parex\Result\DynamicResult;
use Lawondyss\ParexCommander\IO;
use Lawondyss\ParexCommander\ParexCommander;
use Lawondyss\ParexCommander\Type;

require_once __DIR__ . '/../vendor/autoload.php';

$commander = new ParexCommander(
  name: 'Migration DB',
  description: 'Simple tool for managing DB migrations.',
  version: '0.7',
);

$commander->addCommand('migrate', migrate(...), 'Run missing migrations.')
          ->addFlag('dry-run', help: 'It simulates running the migration, but does not execute SQL.');

$commander->addCommand('create', create(...), 'Create new migration file.')
          ->addPositional(Type::string(), 'name', 'Name for migration.')
          ->addOptional(Type::file(false), 'template', help: 'File with template of migration.', default: './migration.tpl');

$commander->addCommand('init', new Init(), 'Initialization of Migration DB.');
$commander->run();


function migrate(DynamicResult $result, IO $io): void
{
  $io->writeLn('Running some migrations.');
  $result->dryRun && $io->writeLn('Launch simulation only.');

  sleep(2);

  if (random_int(0, 1)) {
    $io->writeLn('Success');
    $io->exitSuccess();
  } else {
    $io->writeLn('Failed');
    $io->exitError();
  }
}

function create(DynamicResult $result, IO $io): void
{
  $io->writeLn('Creating migration');
  $io->writeLn("Template: {$result->template}");

  $dir = $io->makeSelection('Found multiple directories with migrations, select one.', ['dir1', 'dir2', 'dir3']);
  $io->writeLn("Migration {$result->name} will be created in {$dir}.");
  $io->exitSuccess();
}

class Init
{
  public function __invoke(DynamicResult $result, IO $io): void
  {
    $configType = $io->makeSelection('Select type of config file.', ['php', 'json', 'neon']);
    $create = $io->makeConfirmation("Do you really want create a config.{$configType} file?");
    $io->writeLn($create ? 'File created.' : 'Bye bye.');
  }
}
