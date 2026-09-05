<?php

namespace App\Livewire\Admin;

use App\Application\Categories\DeleteCategory;
use App\Application\Categories\GetCategory;
use App\Application\Categories\Queries\ListCategories;
use App\Application\Categories\SaveCategory;
use App\Application\Teams\Queries\ListTeamOptions;
use App\Domain\Categories\Exceptions\CategoryNotFound;
use App\Domain\Categories\Exceptions\CategorySlugAlreadyExists;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Livewire\Forms\CategoryForm;
use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class CategoryManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions, WithPagination;

    public string $search = '';

    public bool $isModalOpen = false;

    public CategoryForm $form;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(ListCategories $listCategories, ListTeamOptions $listTeams)
    {
        $this->authorize('viewAny', Category::class);

        return view('livewire.admin.category-manager', [
            'categories' => $listCategories->handle($this->search, 10),
            'teams' => $listTeams->handle(),
        ])->layout('layouts.app');
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);
        $this->form->reset();
        $this->isModalOpen = true;
    }

    public function closeModal(): void
    {
        $this->isModalOpen = false;
        $this->form->resetValidation();
    }

    public function store(GetCategory $getCategory, SaveCategory $saveCategory): void
    {
        try {
            $category = $this->form->selected_id ? $getCategory->handle((int) $this->form->selected_id) : null;

            if ($category) {
                $this->authorize('update', $category);
            } else {
                $this->authorize('create', Category::class);
            }

            $this->form->save($saveCategory);

            session()->flash('message', $this->form->selected_id ? 'Categoria atualizada!' : 'Categoria criada com sucesso!');
            $this->closeModal();
            $this->form->reset();
        } catch (CategorySlugAlreadyExists $e) {
            $this->addError('form.name', $e->getMessage());
        } catch (CategoryNotFound $e) {
            $this->flashException($e);
            $this->closeModal();
            $this->form->reset();
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel salvar a categoria agora.');
        }
    }

    public function edit(int $id, GetCategory $getCategory): void
    {
        try {
            $record = $getCategory->handle($id);
            $this->authorize('update', $record);
            $this->form->setCategory($record);
            $this->isModalOpen = true;
        } catch (CategoryNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel carregar a categoria agora.');
        }
    }

    public function delete(int $id, GetCategory $getCategory, DeleteCategory $deleteCategory): void
    {
        try {
            $record = $getCategory->handle($id);
            $this->authorize('delete', $record);
            $deleteCategory->handle($id);
            session()->flash('message', 'Categoria movida para a lixeira.');
        } catch (CategoryNotFound $e) {
            $this->flashException($e);
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel remover a categoria agora.');
        }
    }
}
