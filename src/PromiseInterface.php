<?php

namespace React\Promise;

interface PromiseInterface
{
    /**
     * @return PromiseInterface
     */
    public function then($onFulfilled = null, $onRejected = null, $onProgress = null);
}
