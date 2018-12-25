<?php

namespace Kelunik\Retry;

interface Backoff {
    /**
     * Calculate the time to pause before retrying.
     *
     * @param int $attempt Current attempt preceding the backoff, will be `1` after the first failure.
     *
     * @return int Number of milliseconds to pause before retrying.
     */
    public function getTimeInMilliseconds(int $attempt): int;
}
