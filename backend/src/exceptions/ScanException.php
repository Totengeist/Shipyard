<?php

namespace Shipyard\Exceptions;

/**
 * Exception that is raised when a queried section is not found.
 */
class ScanException extends \Exception {
    /**
     * @param string $message the error message to display
     */
    public function __construct($message = '') {
        parent::__construct($message);
    }
}
