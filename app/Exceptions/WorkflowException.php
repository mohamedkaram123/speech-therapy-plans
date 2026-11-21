<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when a plan/evaluation workflow transition is not allowed.
 * The message is always user-facing Arabic text.
 */
class WorkflowException extends RuntimeException {}
