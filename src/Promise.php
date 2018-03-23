<?php

namespace React\Promise;

class Promise implements ExtendedPromiseInterface, CancellablePromiseInterface
{
    private $canceller;
    private $result;

    private $handlers = array();
    private $progressHandlers = array();

    private $requiredCancelRequests = 0;
    private $cancelRequests = 0;

    public function __construct($resolver, $canceller = null)
    {
        $this->canceller = $canceller;
        $this->call($resolver);
    }

    public function then($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        if (null !== $this->result) {
            return $this->result->then($onFulfilled, $onRejected, $onProgress);
        }

        if (null === $this->canceller) {
            return new static($this->resolver($onFulfilled, $onRejected, $onProgress));
        }

        $this->requiredCancelRequests++;

        $that = $this;

        return new static($this->resolver($onFulfilled, $onRejected, $onProgress), function () use ($that) {
            if (++$that->cancelRequests < $that->requiredCancelRequests) {
                return;
            }

            $that->cancel();
        });
    }

    public function done($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        if (null !== $this->result) {
            return $this->result->done($onFulfilled, $onRejected, $onProgress);
        }

        $this->handlers[] = function (ExtendedPromiseInterface $promise) use ($onFulfilled, $onRejected) {
            $promise
                ->done($onFulfilled, $onRejected);
        };

        if ($onProgress) {
            $this->progressHandlers[] = $onProgress;
        }
    }

    public function otherwise($onRejected)
    {
        return $this->then(null, function ($reason) use ($onRejected) {
            if (!_checkTypehint($onRejected, $reason)) {
                return new RejectedPromise($reason);
            }

            return $onRejected($reason);
        });
    }

    public function always($onFulfilledOrRejected)
    {
        return $this->then(function ($value) use ($onFulfilledOrRejected) {
            return resolve($onFulfilledOrRejected())->then(function () use ($value) {
                return $value;
            });
        }, function ($reason) use ($onFulfilledOrRejected) {
            return resolve($onFulfilledOrRejected())->then(function () use ($reason) {
                return new RejectedPromise($reason);
            });
        });
    }

    public function progress($onProgress)
    {
        return $this->then(null, null, $onProgress);
    }

    public function cancel()
    {
        if (null === $this->canceller || null !== $this->result) {
            return;
        }

        $canceller = $this->canceller;
        $this->canceller = null;

        $this->call($canceller);
    }

    private function resolver($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        return function ($resolve, $reject, $notify) use ($onFulfilled, $onRejected, $onProgress) {
            if ($onProgress) {
                $progressHandler = function ($update) use ($notify, $onProgress) {
                    try {
                        $notify($onProgress($update));
                    } catch (\Throwable $e) {
                        $notify($e);
                    } catch (\Exception $e) {
                        $notify($e);
                    }
                };
            } else {
                $progressHandler = $notify;
            }

            $this->handlers[] = function (ExtendedPromiseInterface $promise) use ($onFulfilled, $onRejected, $resolve, $reject, $progressHandler) {
                $promise
                    ->then($onFulfilled, $onRejected)
                    ->done($resolve, $reject, $progressHandler);
            };

            $this->progressHandlers[] = $progressHandler;
        };
    }

    private function resolve($value = null)
    {
        if (null !== $this->result) {
            return;
        }

        $this->settle(resolve($value));
    }

    private function reject($reason = null)
    {
        if (null !== $this->result) {
            return;
        }

        $this->settle(reject($reason));
    }

    private function notify($update = null)
    {
        if (null !== $this->result) {
            return;
        }

        foreach ($this->progressHandlers as $handler) {
            $handler($update);
        }
    }

    private function settle(ExtendedPromiseInterface $promise)
    {
        $promise = $this->unwrap($promise);

        $handlers = $this->handlers;

        $this->progressHandlers = $this->handlers = array();
        $this->result = $promise;

        foreach ($handlers as $handler) {
            $handler($promise);
        }
    }

    private function unwrap($promise)
    {
        $promise = $this->extract($promise);

        while ($promise instanceof self && null !== $promise->result) {
            $promise = $this->extract($promise->result);
        }

        return $promise;
    }

    private function extract($promise)
    {
        if ($promise instanceof LazyPromise) {
            $promise = $promise->promise();
        }

        if ($promise === $this) {
            return new RejectedPromise(
                new \LogicException('Cannot resolve a promise with itself.')
            );
        }

        return $promise;
    }

    private function call($callback)
    {
        try {
            $that = $this;
            $callback(
                function ($value = null) use ($that) {
                    $this->resolve($value);
                },
                function ($reason = null) use ($that) {
                    $this->reject($reason);
                },
                function ($update = null) use ($that) {
                    $this->notify($update);
                }
            );
        } catch (\Throwable $e) {
            $this->reject($e);
        } catch (\Exception $e) {
            $this->reject($e);
        }
    }
}
