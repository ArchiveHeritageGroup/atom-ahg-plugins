<?php

namespace AhgVersionControl\Services;

/**
 * Raised by RestoreService when the calling user does not have sufficient
 * security clearance to restore the target entity. The action layer catches
 * this and returns a 403 with the message.
 *
 * @phase J
 */
class InsufficientClearanceException extends \RuntimeException
{
}
