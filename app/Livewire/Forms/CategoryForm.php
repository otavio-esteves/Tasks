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

    #[Validate('required|exists:teams,id')]
    public string|int|null $team_id = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    public function setCategory(Category $category): void
    {
        $this->selected_id = $category->id;
        $this->name = $category->name;
        $this->team_id = $category->team_id;
        $this->description = $category->description;
    }

    public function save(SaveCategory $saveCategory): void
    {
        $this->validate();

        $data = $this->selected_id
            ? UpdateCategoryData::fromArray([
                'name' => (string) $this->name,
                'team_id' => $this->team_id,
                'description' => $this->description,
            ])
            : CreateCategoryData::fromArray([
                'name' => (string) $this->name,
                'team_id' => $this->team_id,
                'description' => $this->description,
            ]);

        $saveCategory->handle($this->selected_id, $data);
    }
}
