<?php

namespace App\Livewire\Admin;

use App\Application\System\Data\SystemAccessData;
use App\Application\System\Queries\GetSystemAccess;
use App\Application\System\UpdateSystemAccess;
use App\Application\Users\Queries\ListPublicAccessCandidates;
use App\Domain\System\Exceptions\InvalidPublicAccessUser;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Throwable;

class SystemAccessManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions;

    public bool $loginRequired = true;

    public string $publicUserId = '';

    public function mount(GetSystemAccess $getSystemAccess): void
    {
        $this->authorize('viewAny', User::class);
        $access = $getSystemAccess->handle();
        $this->loginRequired = $access->loginRequired;
        $this->publicUserId = $access->publicUserId === null ? '' : (string) $access->publicUserId;
    }

    public function save(UpdateSystemAccess $updateSystemAccess): void
    {
        $this->authorize('viewAny', User::class);
        $this->validate([
            'loginRequired' => ['boolean'],
            'publicUserId' => [$this->loginRequired ? 'nullable' : 'required', 'nullable', 'integer'],
        ]);

        try {
            $access = $updateSystemAccess->handle(new SystemAccessData(
                loginRequired: $this->loginRequired,
                publicUserId: $this->publicUserId === '' ? null : (int) $this->publicUserId,
            ));
            $this->loginRequired = $access->loginRequired;
            session()->flash('access-message', 'Configuração de acesso atualizada.');
        } catch (InvalidPublicAccessUser $e) {
            $this->addError('publicUserId', $e->getMessage());
        } catch (Throwable $e) {
            $this->flashUnexpected($e, 'Não foi possível atualizar o acesso agora.', 'access-error');
        }
    }

    public function render(ListPublicAccessCandidates $listPublicAccessCandidates)
    {
        $this->authorize('viewAny', User::class);

        return view('livewire.admin.system-access-manager', [
            'publicUsers' => $listPublicAccessCandidates->handle(),
        ]);
    }
}
