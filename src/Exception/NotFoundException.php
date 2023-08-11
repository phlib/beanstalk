<?php

declare(strict_types=1);

namespace Phlib\Beanstalk\Exception;

/**
 * The `NOT_FOUND` response is defined in the protocol for the following commands:
 *   - reserve-job
 *   - delete
 *   - release
 *   - bury
 *   - touch
 *   - peek
 *   - kick-job
 *   - stats-job
 *   - stats-tube
 *   - pause-tube
 *
 * @package Phlib\Beanstalk
 */
class NotFoundException extends \Exception implements Exception
{
    public const JOB_ID_CODE = 1;

    public const JOB_ID_MSG_F = 'Job id \'%s\' could not be found';

    public const STATS_CODE = 2;

    public const STATS_MSG = 'Stats could not be found for the given entity';

    public const PEEK_STATUS_CODE = 3;

    public const PEEK_STATUS_MSG = 'Peek failed to find any jobs for the given status';
}
