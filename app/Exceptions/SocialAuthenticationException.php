<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Represents failures during third-party authentication and account linking.
 * Used when provider identity data is missing, invalid, or cannot be safely mapped to a local user.
 */
class SocialAuthenticationException extends RuntimeException {}
