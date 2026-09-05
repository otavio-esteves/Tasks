<?php

namespace App\Livewire\Admin;

use App\Application\Teams\DeleteTeam;
use App\Application\Teams\GetTeam;
use App\Application\Teams\Queries\ListTeams;
use App\Application\Teams\SaveTeam;
use App\Domain\Teams\Exceptions\TeamNameAlreadyExists;
use App\Domain\Teams\Exceptions\TeamNotFound;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Livewire\Forms\TeamForm;
use App\Models\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class TeamManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions, WithPagination;

    public string $search = '';

    public bool $isModalOpen = false;

    public TeamForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Team::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListTeams $listTeams)
    {
        $this->authorize('viewAny', Team::class);

        return view('livewire.admin.team-manager', [
            'teams' => $listTeams->handle($this->search, 10),
        ])->layout('layouts.app');
    }

    public function create(): void
    {
        $this->authorize('create', Team::class);
        $this->form->reset();
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->form->resetValidation();
    }

    public function store(GetTeam $getTeam, SaveTeam $saveTeam): void
    {
        try {
            $team = $this->form->selected_id ? $getTeam->handle((int) $this->form->selected_id) : null;

            if ($team) {
                $this->authorize('update', $team);
            } else {
                $this->authorize('create', Team::class);
            }

            $this->form->save($saveTeam);

            session()->flash('message', $this->form->selected_id ? 'Equipe atualizada!' : 'Equipe criada com sucesso!');
            $this->closeModal();
            $this->form->reset();
        } catch (TeamNameAlreadyExists $e) {
            $this->addError('form.name', $e->getMessage());
        } catch (TeamNotFound $e) {
            $this->flashException($e);
            $this->closeModal();
            $this->form->reset();
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel salvar a equipe agora.');
        }
    }

    public function edit(int $id, GetTeam $getTeam): void
    {
        try {
            $record = $getTeam->handle($id);
            $this->authorize('update', $record);
            $this->form->setTeam($record);
            $this->isModalOpen = true;
        } catch (TeamNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel carregar a equipe agora.');
        }
    }

    public function delete(int $id, GetTeam $getTeam, DeleteTeam $deleteTeam): void
    {
        try {
            $record = $getTeam->handle($id);
            $this->authorize('delete', $record);
            $deleteTeam->handle($id);
            session()->flash('message', 'Equipe movida para a lixeira.');
        } catch (TeamNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel remover a equipe agora.');
        }
    }
}
