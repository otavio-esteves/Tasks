<?php

namespace App\Livewire\Concerns;

use Throwable;

trait InteractsWithFriendlyExceptions
{
    protected function flashException(Throwable $exception, string $key = 'message'): void
    {
        session()->flash($key, $exception->getMessage());
    }

    protected function flashUnexpected(Throwable $exception, string $message, string $key = 'message'): void
    {
        report($exception);
        session()->flash($key, $message);
    }
}
