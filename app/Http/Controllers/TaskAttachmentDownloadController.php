<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentDownloadController extends Controller
{
    public function __invoke(Team $team, Task $task, TaskAttachment $attachment)
    {
        Gate::authorize('view', $task);

        abort_unless($task->team_id === $team->id && $attachment->task_id === $task->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }
}
