<?php

namespace App\Models;

/**
 * Alias para Organization. A entidade de negócio é Organization (tabela organizations).
 * Mantido para compatibilidade durante a transição; preferir Organization no novo código.
 */
class Clinic extends Organization
{
    protected $table = 'organizations';
}
