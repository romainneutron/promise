<?php

namespace React\Promise;

class SimpleFulfilledTestThenable
{
    public function then($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        try {
            if ($onFulfilled) {
                $onFulfilled('foo');
            }

            return new self();
        } catch (\Throwable $exception) {
            return new RejectedPromise($exception);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }
}
