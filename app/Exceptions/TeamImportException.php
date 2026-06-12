<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Represents failures in team import workflows.
 * Used for transport, parsing, validation, and persistence errors during team ingestion.
 */
class TeamImportException extends RuntimeException {}
