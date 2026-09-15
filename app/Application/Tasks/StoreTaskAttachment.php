<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskAttachmentRepository;
use App\Application\Tasks\Data\CreateTaskAttachmentData;
use App\Models\TaskAttachment;

class StoreTaskAttachment
{
    public function __construct(
        private readonly GetTask $getTask,
        private readonly TaskAttachmentRepository $attachments,
    ) {}

    public function handle(int $teamId, int $userId, int $taskId, CreateTaskAttachmentData $data): TaskAttachment
    {
        $task = $this->getTask->handle($teamId, $taskId);

        return $this->attachments->create($task, $userId, $data);
    }
}
