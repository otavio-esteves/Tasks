<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskAttachmentRepository;
use App\Domain\Tasks\Exceptions\TaskNotFound;
use App\Models\TaskAttachment;

class DeleteTaskAttachment
{
    public function __construct(
        private readonly GetTask $getTask,
        private readonly TaskAttachmentRepository $attachments,
    ) {}

    public function handle(int $teamId, int $taskId, int $attachmentId): TaskAttachment
    {
        $task = $this->getTask->handle($teamId, $taskId);
        $attachment = $this->attachments->findForTask($task->id, $attachmentId);

        if ($attachment === null) {
            throw new TaskNotFound;
        }

        $this->attachments->delete($attachment);

        return $attachment;
    }
}
