<?php

namespace Lawondyss\ParexCommander\Exception;

use Lawondyss\ParexCommander\Synopsis;
use RuntimeException;

class MissingException extends RuntimeException implements ParexCommanderException
{
  public function __construct(
    string $message,
    public readonly Synopsis $synopsis,
  ) {
    parent::__construct($message);
  }
}
