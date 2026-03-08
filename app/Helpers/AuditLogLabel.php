<?php

namespace App\Helpers;

class AuditLogLabel
{
    private const ACTION_LABELS = [
        'clinic.created' => 'Empresa criada',
        'clinic.updated' => 'Empresa atualizada',
        'user.created' => 'Usuário criado',
        'user.deactivated' => 'Usuário desativado',
        'template.created' => 'Template criado',
        'template.updated' => 'Template atualizado',
        'template.deleted' => 'Template excluído',
        'submission.created' => 'Protocolo criado',
        'submission.reviewed' => 'Protocolo revisado',
        'submission.comment' => 'Comentário em protocolo',
    ];

    private const ENTITY_LABELS = [
        'FormSubmission' => 'Protocolo',
        'User' => 'Usuário',
        'FormTemplate' => 'Template',
        'Clinic' => 'Empresa',
        'Organization' => 'Empresa',
    ];

    private const META_KEY_LABELS = [
        'protocol' => 'protocolo',
        'template_id' => 'template',
        'status' => 'status',
        'comment' => 'comentário',
        'event_id' => 'evento',
    ];

    private const META_VALUE_LABELS = [
        'status' => [
            'approved' => 'aprovado',
            'rejected' => 'rejeitado',
        ],
    ];

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? str_replace('.', ' ', $action);
    }

    public static function entityTypeLabel(?string $entityType): string
    {
        if ($entityType === null || $entityType === '') {
            return '';
        }
        $basename = class_basename($entityType);

        return self::ENTITY_LABELS[$basename] ?? $basename;
    }

    public static function metaKeyLabel(string $key): string
    {
        return self::META_KEY_LABELS[$key] ?? $key;
    }

    public static function metaValueLabel(string $key, mixed $value): string
    {
        if (! is_scalar($value)) {
            return (string) $value;
        }
        $valueStr = (string) $value;
        if (isset(self::META_VALUE_LABELS[$key][$valueStr])) {
            return self::META_VALUE_LABELS[$key][$valueStr];
        }

        return $valueStr;
    }
}
