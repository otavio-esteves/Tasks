<?php

use App\Application\Auth\ResolveUserHomeRoute;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\SecretariatManager;
use App\Livewire\Secretariat\ServiceOrderManager;
use App\Models\Category;
use App\Models\Secretariat;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function (ResolveUserHomeRoute $resolveUserHomeRoute) {
    /** @var User|null $user */
    $user = auth()->user();

    if ($user === null) {
        return redirect()->route('login');
    }

    $target = $resolveUserHomeRoute->handle($user);

    return redirect()->route($target->routeName, $target->parameters);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (ResolveUserHomeRoute $resolveUserHomeRoute) {
        /** @var User $user */
        $user = auth()->user();
        $target = $resolveUserHomeRoute->handle($user);

        if ($target->routeName !== 'dashboard') {
            return redirect()->route($target->routeName, $target->parameters);
        }

        return view('dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/secretarias', SecretariatManager::class)
            ->can('viewAny', Secretariat::class)
            ->name('secretariats');

        Route::get('/categorias', CategoryManager::class)
            ->can('viewAny', Category::class)
            ->name('categories');
    });

    Route::get('/secretarias/{secretariat}/ods', ServiceOrderManager::class)
        ->can('view', 'secretariat')
        ->name('secretariats.ods');
});

require __DIR__.'/auth.php';
