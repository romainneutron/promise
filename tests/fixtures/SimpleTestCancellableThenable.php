<?php

namespace React\Promise;

class SimpleTestCancellableThenable
{
    public $cancelCalled = false;

    public function then($onFulfilled = null, $onRejected = null, $onProgress = null)
    {
        return new self();
    }

    public function cancel()
    {
        $this->cancelCalled = true;
    }
}
