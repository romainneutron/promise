<?php

namespace React\Promise;

interface ExtendedPromiseInterface extends PromiseInterface
{
    /**
     * @return void
     */
    public function done($onFulfilled = null, $onRejected = null, $onProgress = null);

    /**
     * @return ExtendedPromiseInterface
     */
    public function otherwise($onRejected);

    /**
     * @return ExtendedPromiseInterface
     */
    public function always($onFulfilledOrRejected);

    /**
     * @return ExtendedPromiseInterface
     */
    public function progress($onProgress);
}
