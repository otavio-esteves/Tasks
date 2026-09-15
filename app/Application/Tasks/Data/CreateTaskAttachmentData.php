<?php

namespace App\Application\Tasks\Data;

readonly class CreateTaskAttachmentData
{
    public function __construct(
        public string $path,
        public string $originalName,
        public string $mimeType,
        public int $size,
    ) {}
}
