<?php

namespace App\Livewire\Forms;

use App\Application\Secretariats\Data\CreateSecretariatData;
use App\Application\Secretariats\Data\UpdateSecretariatData;
use App\Application\Secretariats\SaveSecretariat;
use App\Models\Secretariat;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SecretariatForm extends Form
{
    public ?int $selected_id = null;

    #[Validate('required|min:3')]
    public string $name = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    public function setSecretariat(Secretariat $secretariat): void
    {
        $this->selected_id = $secretariat->id;
        $this->name = $secretariat->name;
        $this->description = $secretariat->description;
    }

    public function save(SaveSecretariat $saveSecretariat): void
    {
        $this->validate();

        $data = $this->selected_id
            ? UpdateSecretariatData::fromArray([
                'name' => (string) $this->name,
                'description' => $this->description,
            ])
            : CreateSecretariatData::fromArray([
                'name' => (string) $this->name,
                'description' => $this->description,
            ]);

        $saveSecretariat->handle($this->selected_id, $data);
    }
}
