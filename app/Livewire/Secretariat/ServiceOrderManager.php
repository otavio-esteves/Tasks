<?php

namespace App\Livewire\Secretariat;

use App\Application\Categories\Data\CreateCategoryData;
use App\Application\Categories\SaveCategory;
use App\Application\ServiceOrders\ChangeServiceOrderStatus;
use App\Application\ServiceOrders\CreateServiceOrder;
use App\Application\ServiceOrders\Data\CreateServiceOrderData;
use App\Application\ServiceOrders\Data\ServiceOrderListResult;
use App\Application\ServiceOrders\Data\UpdateServiceOrderData;
use App\Application\ServiceOrders\DeleteServiceOrder;
use App\Application\ServiceOrders\GetServiceOrder;
use App\Application\ServiceOrders\Queries\ListServiceOrders;
use App\Application\ServiceOrders\UpdateServiceOrder;
use App\Domain\Categories\Exceptions\CategorySlugAlreadyExists;
use App\Domain\ServiceOrders\Exceptions\InvalidServiceOrderCategory;
use App\Domain\ServiceOrders\Exceptions\InvalidServiceOrderStatusTransition;
use App\Domain\ServiceOrders\Exceptions\ServiceOrderNotFound;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use App\Livewire\Actions\Logout;
use App\Livewire\Concerns\InteractsWithFriendlyExceptions;
use App\Livewire\Forms\ServiceOrderForm;
use App\Models\Category;
use App\Models\Secretariat;
use App\Models\ServiceOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class ServiceOrderManager extends Component
{
    use AuthorizesRequests, InteractsWithFriendlyExceptions, WithPagination;

    public Secretariat $secretariat;

    public ServiceOrderForm $form;

    public string $search = '';

    public string $filterCategoryId = '';

    public string $filterStatus = '';

    public string $filterUrgent = '';

    public string $quickFilter = '';

    public string $newCategoryName = '';

    public bool $showCategoryModal = false;

    public function mount(Secretariat $secretariat): void
    {
        $this->authorize('view', $secretariat);
        $this->authorize('viewAny', [ServiceOrder::class, $secretariat]);
        $this->secretariat = $secretariat;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryId(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updatingFilterUrgent(): void
    {
        $this->quickFilter = '';
        $this->resetPage();
    }

    public function updated($property, $value): void
    {
        if ($property === 'form.categoryId' && $value === 'new') {
            $this->openCategoryModal();
        }
    }

    public function openCategoryModal(): void
    {
        $this->newCategoryName = '';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        if ($this->form->categoryId === 'new') {
            $this->form->categoryId = '';
        }
    }

    public function createNewCategory(SaveCategory $saveCategory): void
    {
        $this->authorize('create', Category::class);

        $name = trim($this->newCategoryName);

        if ($name === '') {
            $this->addError('newCategoryName', 'O nome da categoria é obrigatório.');

            return;
        }

        try {
            $data = CreateCategoryData::fromArray([
                'name' => $name,
                'secretariat_id' => $this->secretariat->id,
            ]);

            $category = $saveCategory->handle(null, $data);

            // Refresh categories list
            $this->secretariat->load('categories');

            // Select the new category
            $this->form->categoryId = $category->id;
            $this->newCategoryName = '';
            $this->showCategoryModal = false;
        } catch (CategorySlugAlreadyExists $e) {
            $this->addError('newCategoryName', 'Já existe uma categoria com este nome.');
        } catch (Throwable $e) {
            $this->addError('newCategoryName', 'Erro ao criar categoria.');
        }
    }

    public function addChecklistItem(?GetServiceOrder $getServiceOrder = null, ?UpdateServiceOrder $updateServiceOrder = null): void
    {
        $this->form->addChecklistItem();

        if ($this->form->odsId) {
            $this->persistChecklistChanges(
                $getServiceOrder ?? app(GetServiceOrder::class),
                $updateServiceOrder ?? app(UpdateServiceOrder::class)
            );
        }
    }

    public function removeChecklistItem(int $index, ?GetServiceOrder $getServiceOrder = null, ?UpdateServiceOrder $updateServiceOrder = null): void
    {
        $this->form->removeChecklistItem($index);

        if ($this->form->odsId) {
            $this->persistChecklistChanges(
                $getServiceOrder ?? app(GetServiceOrder::class),
                $updateServiceOrder ?? app(UpdateServiceOrder::class)
            );
        }
    }

    public function closeModal(?GetServiceOrder $getServiceOrder = null, ?UpdateServiceOrder $updateServiceOrder = null): void
    {
        $getServiceOrder = $getServiceOrder ?? app(GetServiceOrder::class);
        $updateServiceOrder = $updateServiceOrder ?? app(UpdateServiceOrder::class);

        try {
            if (trim((string) $this->form->newChecklistItem) !== '') {
                $this->form->addChecklistItem();
            }

            if ($this->form->shouldPersistChecklistOnClose()) {
                $this->persistChecklistChanges($getServiceOrder, $updateServiceOrder);
            }

            $this->resetForm();
            $this->dispatch('ods-modal-closed');
        } catch (InvalidServiceOrderCategory|ServiceOrderNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable) {
            $this->flashFallback('Não foi possível salvar a checklist agora. Tente novamente.', 'error');
        }
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function selectStatus(string $status): void
    {
        if ($this->form->odsId) {
            $this->updateStatus((int) $this->form->odsId, $status);
        } else {
            $this->form->currentStatus = $status;
        }
    }

    public function updateStatus(
        int $id,
        string $status,
        ?GetServiceOrder $getServiceOrder = null,
        ?ChangeServiceOrderStatus $changeServiceOrderStatus = null
    ): void {
        $getServiceOrder = $getServiceOrder ?? app(GetServiceOrder::class);
        $changeServiceOrderStatus = $changeServiceOrderStatus ?? app(ChangeServiceOrderStatus::class);

        try {
            $serviceOrder = $getServiceOrder->handle($this->secretariat->id, $id);
            $this->authorize('update', $serviceOrder);

            $changeServiceOrderStatus->handle($this->secretariat->id, auth()->id(), $id, ServiceOrderStatus::from($status));

            if ((int) $this->form->odsId === $id) {
                $this->edit($id, 'details', $getServiceOrder);
            }

            $this->dispatch('ods-status-updated');
        } catch (InvalidServiceOrderCategory|InvalidServiceOrderStatusTransition|ServiceOrderNotFound|\ValueError $e) {
            $this->flashException($e, 'error');
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel atualizar o status agora. Tente novamente.', 'error');
        }
    }

    public function save(
        ?GetServiceOrder $getServiceOrder = null,
        ?CreateServiceOrder $createServiceOrder = null,
        ?UpdateServiceOrder $updateServiceOrder = null
    ): void {
        $getServiceOrder = $getServiceOrder ?? app(GetServiceOrder::class);
        $createServiceOrder = $createServiceOrder ?? app(CreateServiceOrder::class);
        $updateServiceOrder = $updateServiceOrder ?? app(UpdateServiceOrder::class);

        $this->form->validate();

        try {
            if ($this->form->odsId) {
                $serviceOrder = $getServiceOrder->handle($this->secretariat->id, (int) $this->form->odsId);
                $this->authorize('update', $serviceOrder);

                $data = UpdateServiceOrderData::fromArray($this->form->formState());
                $updateServiceOrder->handle($this->secretariat->id, auth()->id(), (int) $this->form->odsId, $data);
            } else {
                $this->authorize('create', [ServiceOrder::class, $this->secretariat]);

                $data = CreateServiceOrderData::fromArray($this->form->formState());
                $createServiceOrder->handle($this->secretariat->id, auth()->id(), $data);
            }

            $this->resetForm();
            $this->dispatch('ods-saved');
        } catch (InvalidServiceOrderCategory|ServiceOrderNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel salvar a ordem de servico agora. Tente novamente.', 'error');
        }
    }

    public function edit(int $id, string $view = 'details', ?GetServiceOrder $getServiceOrder = null): void
    {
        $getServiceOrder = $getServiceOrder ?? app(GetServiceOrder::class);

        try {
            $ods = $getServiceOrder->handle($this->secretariat->id, $id);
            $this->authorize('update', $ods);

            $this->form->setServiceOrder($ods);
            $this->dispatch('open-ods-modal', mode: 'edit', view: $view);
        } catch (ServiceOrderNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel carregar a ordem de servico.', 'error');
        }
    }

    public function delete(int $id, ?GetServiceOrder $getServiceOrder = null, ?DeleteServiceOrder $deleteServiceOrder = null): void
    {
        $getServiceOrder = $getServiceOrder ?? app(GetServiceOrder::class);
        $deleteServiceOrder = $deleteServiceOrder ?? app(DeleteServiceOrder::class);

        try {
            $serviceOrder = $getServiceOrder->handle($this->secretariat->id, $id);
            $this->authorize('delete', $serviceOrder);

            $deleteServiceOrder->handle($this->secretariat->id, $id);

            session()->flash('success', 'Ordem removida com sucesso!');
        } catch (ServiceOrderNotFound $e) {
            $this->flashException($e, 'error');
        } catch (Throwable) {
            $this->flashFallback('Nao foi possivel remover a ordem de servico agora.', 'error');
        }
    }

    public function resetForm(): void
    {
        $this->form->reset();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterCategoryId', 'filterStatus', 'filterUrgent', 'quickFilter']);
        $this->resetPage();
    }

    public function applyQuickFilter(string $filter): void
    {
        $this->quickFilter = $filter === 'total' || $this->quickFilter === $filter
            ? ''
            : $filter;

        $this->resetPage();
    }

    public function render(ListServiceOrders $listServiceOrders)
    {
        $this->authorize('viewAny', [ServiceOrder::class, $this->secretariat]);

        /** @var ServiceOrderListResult $listing */
        $listing = $listServiceOrders->handle($this->secretariat->id, $this->search, $this->listFilters(), 15);

        return view('livewire.secretariat.service-order-manager', [
            'serviceOrders' => $listing->serviceOrders,
            'summary' => $listing->summary,
            'categories' => $this->secretariat->categories,
            'statusOptions' => ServiceOrderStatus::cases(),
        ])->layout('layouts.app');
    }

    /**
     * @return array{category_id?:int,status?:string,urgent?:bool,quick_filter?:string}
     */
    private function listFilters(): array
    {
        $filters = [];

        if ($this->filterCategoryId !== '') {
            $filters['category_id'] = (int) $this->filterCategoryId;
        }

        if ($this->filterStatus !== '') {
            $filters['status'] = (string) $this->filterStatus;
        }

        if ($this->filterUrgent !== '') {
            $filters['urgent'] = $this->filterUrgent === '1';
        }

        if ($this->quickFilter !== '') {
            $filters['quick_filter'] = (string) $this->quickFilter;
        }

        return $filters;
    }

    public function toggleChecklistItem(int $index, ?GetServiceOrder $getServiceOrder = null, ?UpdateServiceOrder $updateServiceOrder = null): void
    {
        $this->form->checklistItems[$index]['is_completed'] = ! ($this->form->checklistItems[$index]['is_completed'] ?? false);

        if ($this->form->odsId) {
            $this->persistChecklistChanges(
                $getServiceOrder ?? app(GetServiceOrder::class),
                $updateServiceOrder ?? app(UpdateServiceOrder::class)
            );
        }
    }

    private function persistChecklistChanges(GetServiceOrder $getServiceOrder, UpdateServiceOrder $updateServiceOrder): void
    {
        $serviceOrder = $getServiceOrder->handle($this->secretariat->id, (int) $this->form->odsId);
        $this->authorize('update', $serviceOrder);

        /** @var Carbon|null $dueDate */
        $dueDate = $serviceOrder->due_date;

        $data = UpdateServiceOrderData::fromArray([
            'title' => $serviceOrder->title,
            'location' => $serviceOrder->location ?? '',
            'category_id' => $serviceOrder->category_id,
            'due_date' => $dueDate?->format('Y-m-d') ?? '',
            'is_urgent' => (bool) $serviceOrder->is_urgent,
            'observation' => $serviceOrder->observation ?? '',
            'checklist_items' => $this->form->checklistItems,
        ]);

        $updated = $updateServiceOrder->handle($this->secretariat->id, auth()->id(), (int) $this->form->odsId, $data);

        $toState = UpdateServiceOrderData::fromServiceOrder($updated)->toFormState();
        $this->form->checklistItems = $toState['checklistItems'];
        $this->form->historyItems = $toState['historyItems'];
        $this->form->originalChecklistItems = $this->form->normalizeChecklistItems($this->form->checklistItems);
    }
}
