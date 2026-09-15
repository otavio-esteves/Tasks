<?php

namespace App\Application\Tasks\Contracts;

use App\Application\Tasks\Data\CreateTaskAttachmentData;
use App\Models\Task;
use App\Models\TaskAttachment;

interface TaskAttachmentRepository
{
    public function create(Task $task, int $userId, CreateTaskAttachmentData $data): TaskAttachment;

    public function findForTask(int $taskId, int $attachmentId): ?TaskAttachment;

    public function delete(TaskAttachment $attachment): void;
}
