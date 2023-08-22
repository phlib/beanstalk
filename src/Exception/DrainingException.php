<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Exception;

/**
 * The `DRAINING` response is defined in the protocol for the `put` command,
 * and is returned when the server has been put into 'drain mode' and is no longer accepting new jobs.
 *
 * @package Phlib\Beanstalk
 */
class DrainingException extends CommandException
{
    public const PUT_CODE = 1;

    public const PUT_MSG = 'Server is in drain mode';
}
