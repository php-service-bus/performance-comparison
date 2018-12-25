<?php

namespace Kelunik\Retry;

use Amp\Delayed;
use Amp\Promise;
use function Amp\call;

/**
 * Retries an action the specified number of times if it fails with one of the listed exceptions.
 *
 * @param int             $maxAttempts Maximum number of attempts, must be at least 1.
 * @param callable        $actor Callable to retry. Will be called with `Amp\call()`.
 * @param string|string[] $throwable Exception classes to catch, either a single class name or an array of class names.
 * @param Backoff|null    $backoff Optional backoff to apply between attempts. Defaults to a constant backoff of 0.
 *
 * @return Promise Resolves to the original value of `Amp\call($actor)` or fails with the same exception on too many
 *     attempts.
 *
 * @throws \Error If `$maxAttempts` is non-positive or `$throwable` is an empty array.
 * @throws \TypeError If `$throwable` contains invalid types.
 */
function retry(int $maxAttempts, callable $actor, $throwable = \Exception::class, Backoff $backoff = null): Promise {
    if ($maxAttempts < 1) {
        throw new \Error("Argument 1 (maxAttempts) must be positive.");
    }

    if ($throwable === []) {
        throw new \Error("Argument 3 (throwable) can't be an empty array.");
    }

    if (!\is_array($throwable)) {
        $throwable = [$throwable];
    }

    /** @var string[] $throwable */
    foreach ($throwable as $t) {
        if (!\is_string($t)) {
            throw new \TypeError("Argument 3 (throwable) was expected to be an array of strings or a single string.");
        }
    }

    return call(function () use ($maxAttempts, $actor, $throwable, $backoff) {
        $attempt = 0;
        $backoff = $backoff ?? new ConstantBackoff(0);

        retry:

        $attempt++;

        try {
            $result = yield call($actor);
        } catch (\Throwable $e) {
            if ($attempt < $maxAttempts) {
                /** @var string[] $throwable */
                foreach ($throwable as $t) {
                    if ($e instanceof $t) {
                        yield new Delayed($backoff->getTimeInMilliseconds($attempt));
                        goto retry;
                    }
                }
            }

            throw $e;
        }

        return $result;
    });
}
