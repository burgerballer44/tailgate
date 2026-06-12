<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Represents failures in game import workflows.
 * Used for transport, parsing, validation, and persistence errors during game ingestion.
 */
class GameImportException extends RuntimeException {}
