<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an error occurs during team import operations.
 * This may include API request failures, data validation errors, or malformed response payloads.
 */
class TeamImportException extends RuntimeException {}
