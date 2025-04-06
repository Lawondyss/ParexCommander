<?php

use Lawondyss\Parex\Parex;
use Lawondyss\Parex\Parser\ArgvParser;
use Lawondyss\Parex\Result\DynamicResult;
use Lawondyss\ParexCommander\Command;
use Lawondyss\ParexCommander\Console\Writer;
use Lawondyss\ParexCommander\IO;
use Lawondyss\ParexCommander\Type;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @param object{env: string, sandbox: bool, user: string} $result
 */
function import(DynamicResult $result, IO $io): never
{
  $io->writeHeader("Import of system's data");
  $result->sandbox && $io->writeLn('Testing');
  $io->writeLn("'Environment: {$result->env} User: {$result->user} Server: ", $result->sandbox ? 'dev' : 'prod');

  $sources = $io->makeSelection('Select source(s):', ['server', 'database', 'files'], multiple: true);

  $scope = $io->makeSelection('Select date scope', [
    'yesterday' => sprintf(
      'Yesterday (%s)',
      date('Y-m-d', strtotime('yesterday'))
    ),
    'last:week' => sprintf(
      'Last week (%s > %s)',
      date('Y-m-d', strtotime('first day of last week')),
      date('Y-m-d', strtotime('last day of last week')),
    ),
    'last:month' => sprintf(
      'Last month (%s > %s)',
      date('Y-m-d', strtotime('first day of last month')),
      date('Y-m-d', strtotime('last day of last month')),
    ),
    'own' => 'I will manually enter',
  ]);

  if ($scope === 'own') {
    $scope = $io->makeQuestion(
      prompt: 'Enter date scope:',
      validator: static function (string $value): bool|string {
        if (str_contains($value, '>')) {
          return count(array_filter(
            array_map(trim(...), (explode('>', $value) + ['', ''])),
            static fn (string $date) => (bool)DateTimeImmutable::createFromFormat('Y-m-d', $date),
          )) === 2
            ? true
            : 'Range scope must have format "YYYY-MM-DD > YYYY-MM-DD"';
        } else {
          return DateTimeImmutable::createFromFormat('Y-m-d', $value)
            ? true
            : 'Date scope must have format "YYYY-MM-DD"';
        }
      },
    );
  }

  if (!$result->sandbox && !$io->makeConfirmation('Do you really want to import production data?')) {
    $io->writeLn('Import aborted.');
    $io->exitSuccess();
  }

  $label = 'Importing ' . implode(' and ', $sources) . " for {$scope}";
  $result = $io->monitoring($label, function (Writer $writer): string {
    for ($i = 5; $i < 15; $i++) {
      usleep(1000_000);
      $writer->writeLn("Processing item #{$i}");
    }

    return random_int(0, 3) ? 'Import finished.' : 'Import FAILED!';
  });
  $io->writeLn($result);

  $io->exitSuccess();
}

$command = new Command('import', import(...), 'Importing something...', '1.0');
$command->addPositional(Type::email(), 'user', 'Author of import.')
        ->addOptional(Type::file(false), 'env', 'e', 'Path to .env file.', './.env')
        ->addFlag('sandbox', help: 'Connecting to dev server instead of production.')
        ->run(new Parex(new ArgvParser()), new IO());
