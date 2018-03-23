<?php

namespace React\Promise;

class LazyPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $factory;
    private $promise;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    public function then($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        return $this->promise()->then($onFulfilled, $onRejected, $onProgress);
    }

    public function done($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        return $this->promise()->done($onFulfilled, $onRejected, $onProgress);
    }

    public function otherwise($onRejected)
    {
        return $this->promise()->otherwise($onRejected);
    }

    public function always($onFulfilledOrRejected)
    {
        return $this->promise()->always($onFulfilledOrRejected);
    }

    public function progress($onProgress)
    {
        return $this->promise()->progress($onProgress);
    }

    public function cancel()
    {
        return $this->promise()->cancel();
    }

    /**
     * @internal
     * @see Promise::settle()
     */
    public function promise()
    {
        if (null === $this->promise) {
            try {
                $this->promise = resolve(call_user_func($this->factory));
            } catch (\Throwable $exception) {
                $this->promise = new RejectedPromise($exception);
            } catch (\Exception $exception) {
                $this->promise = new RejectedPromise($exception);
            }
        }

        return $this->promise;
    }
}
