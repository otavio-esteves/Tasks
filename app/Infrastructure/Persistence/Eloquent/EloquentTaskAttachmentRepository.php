<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Application\Tasks\Contracts\TaskAttachmentRepository;
use App\Application\Tasks\Data\CreateTaskAttachmentData;
use App\Models\Task;
use App\Models\TaskAttachment;

class EloquentTaskAttachmentRepository implements TaskAttachmentRepository
{
    public function create(Task $task, int $userId, CreateTaskAttachmentData $data): TaskAttachment
    {
        return $task->attachments()->create([
            'user_id' => $userId,
            'path' => $data->path,
            'original_name' => $data->originalName,
            'mime_type' => $data->mimeType,
            'size' => $data->size,
        ]);
    }

    public function findForTask(int $taskId, int $attachmentId): ?TaskAttachment
    {
        return TaskAttachment::query()
            ->where('task_id', $taskId)
            ->whereKey($attachmentId)
            ->first();
    }

    public function delete(TaskAttachment $attachment): void
    {
        $attachment->delete();
    }
}
