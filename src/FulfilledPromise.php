<?php

namespace React\Promise;

class FulfilledPromise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $value;

    public function __construct($value = null)
    {
        if ($value instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\FulfilledPromise with a promise. Use React\Promise\resolve($promiseOrValue) instead.');
        }

        $this->value = $value;
    }

    public function then($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        if (null === $onFulfilled) {
            return $this;
        }

        try {
            return resolve($onFulfilled($this->value));
        } catch (\Throwable $exception) {
            return new RejectedPromise($exception);
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function done($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        if (null === $onFulfilled) {
            return;
        }

        $result = $onFulfilled($this->value);

        if ($result instanceof ExtendedPromiseInterface) {
            $result->done();
        }
    }

    public function otherwise($onRejected)
    {
        return $this;
    }

    public function always($onFulfilledOrRejected)
    {
        return $this->then(function ($value) use ($onFulfilledOrRejected) {
            return resolve($onFulfilledOrRejected())->then(function () use ($value) {
                return $value;
            });
        });
    }

    public function progress($onProgress)
    {
        return $this;
    }

    public function cancel()
    {
    }
}
