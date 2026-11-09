<?php

namespace App\Services\FormImport;

interface DocumentExtractionProvider
{
    /**
     * @return array{fields: list<array<string, mixed>>, warnings: list<array<string, mixed>>, provider: string, model_version: string}
     */
    public function extract(string $absolutePath, string $mime, ?string $originalName = null): array;

    /**
     * Extrai de uma ou mais páginas/arquivos (ex.: várias fotos da mesma ficha).
     *
     * @param  list<array{path: string, mime: string, name: ?string, page: int}>  $sources
     * @return array{fields: list<array<string, mixed>>, warnings: list<array<string, mixed>>, provider: string, model_version: string}
     */
    public function extractMany(array $sources): array;
}
