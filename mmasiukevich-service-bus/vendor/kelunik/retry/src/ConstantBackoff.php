<?php

namespace Kelunik\Retry;

class ConstantBackoff implements Backoff {
    private $backoff;

    public function __construct(int $backoff) {
        if ($backoff < 0) {
            throw new \Error("Argument one (backoff) must be non-negative, got {$backoff}.");
        }

        $this->backoff = $backoff;
    }

    /** @inheritdoc */
    public function getTimeInMilliseconds(int $attempt): int {
        return $this->backoff;
    }
}
