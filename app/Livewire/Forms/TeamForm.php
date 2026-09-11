<?php

namespace App\Livewire\Forms;

use App\Application\Teams\Data\CreateTeamData;
use App\Application\Teams\Data\UpdateTeamData;
use App\Application\Teams\SaveTeam;
use App\Models\Team;
use Livewire\Attributes\Validate;
use Livewire\Form;

class TeamForm extends Form
{
    public ?int $selected_id = null;

    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('nullable|string')]
    public ?string $description = '';

    public function setTeam(Team $team): void
    {
        $this->selected_id = $team->id;
        $this->name = $team->name;
        $this->description = $team->description;
    }

    public function save(SaveTeam $saveTeam): void
    {
        $this->name = trim($this->name);
        $this->validate();

        $data = $this->selected_id
            ? UpdateTeamData::fromArray([
                'name' => (string) $this->name,
                'description' => $this->description,
            ])
            : CreateTeamData::fromArray([
                'name' => (string) $this->name,
                'description' => $this->description,
            ]);

        $saveTeam->handle($this->selected_id, $data);
    }
}
