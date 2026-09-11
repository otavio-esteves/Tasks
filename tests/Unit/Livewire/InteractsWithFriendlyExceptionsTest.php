<?php

namespace Tests\Unit\Livewire;

use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use Illuminate\Contracts\Debug\ExceptionHandler;
use RuntimeException;
use Tests\TestCase;
use Throwable;

class InteractsWithFriendlyExceptionsTest extends TestCase
{
    public function test_unexpected_exceptions_are_reported_without_being_exposed(): void
    {
        $exception = new RuntimeException('internal database detail');

        $this->mock(ExceptionHandler::class)
            ->shouldReceive('report')
            ->once()
            ->with($exception);

        $this->componentUsingConcern()->flashUnexpectedForTest($exception, 'Mensagem amigavel.', 'error');

        $this->assertSame('Mensagem amigavel.', session('error'));
        $this->assertNotSame($exception->getMessage(), session('error'));
    }

    public function test_expected_domain_exceptions_are_not_reported(): void
    {
        $exception = new RuntimeException('Mensagem de dominio.');

        $this->mock(ExceptionHandler::class)
            ->shouldNotReceive('report');

        $this->componentUsingConcern()->flashExceptionForTest($exception, 'error');

        $this->assertSame('Mensagem de dominio.', session('error'));
    }

    private function componentUsingConcern(): object
    {
        return new class
        {
            use InteractsWithFriendlyExceptions;

            public function flashUnexpectedForTest(Throwable $exception, string $message, string $key): void
            {
                $this->flashUnexpected($exception, $message, $key);
            }

            public function flashExceptionForTest(Throwable $exception, string $key): void
            {
                $this->flashException($exception, $key);
            }
        };
    }
}
