<?php

use Lawondyss\ParexCommander\Console\Writer;
use Lawondyss\ParexCommander\IO;

require_once __DIR__ . '/../vendor/autoload.php';

$io = new IO();

$io->clearScreen();
$io->writeHeader("Parex Commander\nExample of using a IO class");

$go = $io->makeConfirmation(
  prompt: 'Do you want to continue?'
);
$io->writeLn($go ? 'Thanks 😊' : 'Err... 🫨');

if (!$go) {
  $io->exitError(code: 128);
}
$io->monitoring(
  label: "Wait a minute, I'm preparing questions.",
  callback: static function (Writer $writer): void {
    $phrases = ["I'm thinking...", "I'm writing...", "Where's my paper?", "Where's my pencil?", "I'd like some coffee."];
    $last = -1;

    for ($i = 0; $i < 10; $i++) {
      do {
        $index = random_int(0, 4);
      } while ($index === $last);
      $writer->writeLn($phrases[$index]);
      $last = $index;
      sleep(1);
    }
  },
  lines: 2,
);

$name = $io->makeQuestion(
  prompt: 'What is your name?',
  validator: fn (string $input) => strlen($input) === 0 ? '⛔️ Name is required' : true,
);
$io->writeLn("Hi, $name 👋");

$purpose = $io->makeSelection(
  prompt: 'What is your purpose?',
  options: [
    'ac' => 'App creator',
    'e' => 'Explorer',
    '?' => '42',
  ],
);
$io->writeLn(match ($purpose) {
  'ac' => 'An excellent choice 👍',
  'e' => 'You found a powerful tool 🦾',
  '?' => '🐋🪴',
  default => 'What?!'
});

$frameworks = $io->makeSelection(
  prompt: 'What is your favourite JS framework?',
  options: ['Angular', 'React', 'Svelte', 'Vue'],
  multiple: true,
);
$io->writeLn('WOW! I 💙 ', implode(' and ', $frameworks), ' too!');

$io->writeLn("Thanks, we'll get back to you.");
$io->exitSuccess();
