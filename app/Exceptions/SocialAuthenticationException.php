<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown when an error occurs during social authentication operations.
 * This may include provider connection failures, missing credentials, or invalid user data from the provider.
 */
class SocialAuthenticationException extends RuntimeException {}
