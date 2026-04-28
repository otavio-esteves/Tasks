<?php

namespace App\Livewire\Forms;

use App\Application\Categories\Data\CreateCategoryData;
use App\Application\Categories\Data\UpdateCategoryData;
use App\Application\Categories\SaveCategory;
use App\Models\Category;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CategoryForm extends Form
{
    public ?int $selected_id = null;

    #[Validate('required|min:3')]
    public string $name = '';

    #[Validate('required|exists:secretariats,id')]
    public string|int|null $secretariat_id = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    public function setCategory(Category $category): void
    {
        $this->selected_id = $category->id;
        $this->name = $category->name;
        $this->secretariat_id = $category->secretariat_id;
        $this->description = $category->description;
    }

    public function save(SaveCategory $saveCategory): void
    {
        $this->validate();

        $data = $this->selected_id
            ? UpdateCategoryData::fromArray([
                'name' => (string) $this->name,
                'secretariat_id' => $this->secretariat_id,
                'description' => $this->description,
            ])
            : CreateCategoryData::fromArray([
                'name' => (string) $this->name,
                'secretariat_id' => $this->secretariat_id,
                'description' => $this->description,
            ]);

        $saveCategory->handle($this->selected_id, $data);
    }
}
