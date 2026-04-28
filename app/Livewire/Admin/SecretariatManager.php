<?php

namespace App\Livewire\Admin;

use App\Application\Secretariats\DeleteSecretariat;
use App\Application\Secretariats\GetSecretariat;
use App\Application\Secretariats\Queries\ListSecretariats;
use App\Application\Secretariats\SaveSecretariat;
use App\Domain\Secretariats\Exceptions\SecretariatNameAlreadyExists;
use App\Domain\Secretariats\Exceptions\SecretariatNotFound;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Livewire\Forms\SecretariatForm;
use App\Models\Secretariat;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class SecretariatManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions, WithPagination;

    public string $search = '';

    public bool $isModalOpen = false;

    public SecretariatForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Secretariat::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListSecretariats $listSecretariats)
    {
        $this->authorize('viewAny', Secretariat::class);

        return view('livewire.admin.secretariat-manager', [
            'secretariats' => $listSecretariats->handle($this->search, 10),
        ])->layout('layouts.app');
    }

    public function create(): void
    {
        $this->authorize('create', Secretariat::class);
        $this->form->reset();
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->form->resetValidation();
    }

    public function store(GetSecretariat $getSecretariat, SaveSecretariat $saveSecretariat): void
    {
        try {
            $secretariat = $this->form->selected_id ? $getSecretariat->handle((int) $this->form->selected_id) : null;

            if ($secretariat) {
                $this->authorize('update', $secretariat);
            } else {
                $this->authorize('create', Secretariat::class);
            }

            $this->form->save($saveSecretariat);

            session()->flash('message', $this->form->selected_id ? 'Secretaria atualizada!' : 'Secretaria criada com sucesso!');
            $this->closeModal();
            $this->form->reset();
        } catch (SecretariatNameAlreadyExists $e) {
            $this->addError('form.name', $e->getMessage());
        } catch (SecretariatNotFound $e) {
            $this->flashException($e);
            $this->closeModal();
            $this->form->reset();
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel salvar a secretaria agora.');
        }
    }

    public function edit(int $id, GetSecretariat $getSecretariat): void
    {
        try {
            $record = $getSecretariat->handle($id);
            $this->authorize('update', $record);
            $this->form->setSecretariat($record);
            $this->isModalOpen = true;
        } catch (SecretariatNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel carregar a secretaria agora.');
        }
    }

    public function delete(int $id, GetSecretariat $getSecretariat, DeleteSecretariat $deleteSecretariat): void
    {
        try {
            $record = $getSecretariat->handle($id);
            $this->authorize('delete', $record);
            $deleteSecretariat->handle($id);
            session()->flash('message', 'Secretaria movida para a lixeira.');
        } catch (SecretariatNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel remover a secretaria agora.');
        }
    }
}
