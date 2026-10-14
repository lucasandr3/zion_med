<?php

namespace App\Services\FormImport;

interface DocumentExtractionProvider
{
    /**
     * @return array{fields: list<array<string, mixed>>, warnings: list<array<string, mixed>>, provider: string, model_version: string}
     */
    public function extract(string $absolutePath, string $mime, ?string $originalName = null): array;
}
