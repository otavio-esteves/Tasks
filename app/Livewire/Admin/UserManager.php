<?php

namespace App\Livewire\Admin;

use App\Application\Teams\Queries\ListTeamOptions;
use App\Application\Users\ChangeUserRole;
use App\Application\Users\ChangeUserTeams;
use App\Application\Users\CreateUser;
use App\Application\Users\Data\CreateUserData;
use App\Application\Users\GetUser;
use App\Application\Users\Queries\ListUsers;
use App\Domain\Users\Exceptions\InvalidUserTeam;
use App\Domain\Users\Exceptions\LastAdministratorRequired;
use App\Domain\Users\Exceptions\PublicAccessUserCannotBeAdministrator;
use App\Domain\Users\Exceptions\UserEmailAlreadyExists;
use App\Domain\Users\Exceptions\UserNotFound;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Throwable;

class UserManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions;

    public bool $isCreateOpen = false;

    public bool $isTeamEditOpen = false;

    public ?int $editingUserId = null;

    public string $editingUserName = '';

    public bool $editingUserIsAdministrator = false;

    /** @var list<string> */
    public array $editTeamIds = [];

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $teamId = '';

    public bool $isAdministrator = false;

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function setRole(int $userId, string $role, GetUser $getUser, ChangeUserRole $changeUserRole): void
    {
        if (! in_array($role, ['admin', 'common'], true)) {
            return;
        }

        try {
            $user = $getUser->handle($userId);
            $this->authorize('update', $user);
            $changeUserRole->handle($userId, $role === 'admin');
            session()->flash('user-message', 'Cargo atualizado com sucesso.');
        } catch (LastAdministratorRequired|PublicAccessUserCannotBeAdministrator|UserNotFound $e) {
            $this->flashException($e, 'user-error');
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Não foi possível atualizar o cargo agora.', 'user-error');
        }
    }

    public function openCreate(): void
    {
        $this->authorize('create', User::class);
        $this->resetCreateForm();
        $this->isCreateOpen = true;
    }

    public function closeCreate(): void
    {
        $this->isCreateOpen = false;
        $this->resetCreateForm();
    }

    public function openTeamEdit(int $userId, GetUser $getUser): void
    {
        $user = $getUser->handle($userId);
        $this->authorize('update', $user);

        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->editingUserName = $user->name;
        $this->editingUserIsAdministrator = $user->isAdmin();
        $this->editTeamIds = collect([$user->team_id, ...$user->additionalTeams->pluck('id')->all()])
            ->filter(fn ($id): bool => $id !== null)
            ->map(fn ($id): string => (string) $id)
            ->all();
        $this->isTeamEditOpen = true;
    }

    public function closeTeamEdit(): void
    {
        $this->reset(['isTeamEditOpen', 'editingUserId', 'editingUserName', 'editingUserIsAdministrator', 'editTeamIds']);
        $this->resetValidation();
    }

    public function saveTeams(GetUser $getUser, ChangeUserTeams $changeUserTeams): void
    {
        if ($this->editingUserId === null) {
            return;
        }

        $user = $getUser->handle($this->editingUserId);
        $this->authorize('update', $user);
        $validated = $this->validate([
            'editTeamIds' => ['array'],
            'editTeamIds.*' => ['integer', 'distinct'],
        ]);

        try {
            $teamIds = array_map('intval', $validated['editTeamIds']);
            $changeUserTeams->handle($user->id, $teamIds, (int) auth()->id());
            $this->closeTeamEdit();
            session()->flash('user-message', 'Equipes do usuário atualizadas com sucesso.');
        } catch (InvalidUserTeam $e) {
            $this->addError('editTeamIds', $e->getMessage());
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Não foi possível atualizar a equipe agora.', 'user-error');
        }
    }

    public function create(CreateUser $createUser): void
    {
        $this->authorize('create', User::class);
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
            'teamId' => [$this->isAdministrator ? 'nullable' : 'required', 'nullable', 'integer'],
            'isAdministrator' => ['boolean'],
        ]);

        try {
            $createUser->handle(CreateUserData::fromArray([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'team_id' => $validated['teamId'] ?? null,
                'is_admin' => $validated['isAdministrator'],
            ]));
            $this->closeCreate();
            session()->flash('user-message', 'Usuário criado com sucesso.');
        } catch (InvalidUserTeam $e) {
            $this->addError('teamId', $e->getMessage());
        } catch (UserEmailAlreadyExists $e) {
            $this->addError('email', $e->getMessage());
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Não foi possível criar o usuário agora.', 'user-error');
        }
    }

    public function render(ListUsers $listUsers, ListTeamOptions $listTeamOptions)
    {
        $this->authorize('viewAny', User::class);

        return view('livewire.admin.user-manager', [
            'users' => $listUsers->handle(),
            'teams' => $listTeamOptions->handle(),
        ]);
    }

    private function resetCreateForm(): void
    {
        $this->reset(['name', 'email', 'password', 'passwordConfirmation', 'teamId', 'isAdministrator']);
        $this->resetValidation();
    }
}
