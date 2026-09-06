<?php

use App\Application\Auth\ResolveUserHomeRoute;
use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\TeamManager;
use App\Livewire\Team\TaskManager;
use App\Models\Category;
use App\Models\Team;
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

    Route::view('/aguardando-acesso', 'auth.pending-access')->name('access.pending');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/equipes', TeamManager::class)
            ->can('viewAny', Team::class)
            ->name('teams');

        Route::get('/categorias', CategoryManager::class)
            ->can('viewAny', Category::class)
            ->name('categories');
    });

    Route::get('/equipes/{team}/tarefas', TaskManager::class)
        ->can('view', 'team')
        ->name('teams.tasks');

    // Keep existing bookmarks usable during the transition to Tasks.
    Route::get('/admin/secretarias', fn () => redirect()->route('admin.teams'))
        ->can('viewAny', Team::class);

    Route::get('/secretarias/{team}/ods', fn (Team $team) => redirect()->route('teams.tasks', $team))
        ->can('view', 'team');
});

require __DIR__.'/auth.php';
