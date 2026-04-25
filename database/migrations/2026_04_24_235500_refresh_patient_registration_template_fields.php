<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $templateIds = DB::table('form_templates')
            ->where('name', 'Cadastro do Paciente (Básico)')
            ->pluck('id');

        if ($templateIds->isEmpty()) {
            return;
        }

        $now = now();
        $fields = [
            ['type' => 'text', 'label' => 'Nome completo', 'name_key' => 'nome_completo', 'required' => true, 'options_json' => null, 'sort_order' => 1],
            ['type' => 'date', 'label' => 'Data de nascimento', 'name_key' => 'data_nascimento', 'required' => true, 'options_json' => null, 'sort_order' => 2],
            ['type' => 'number', 'label' => 'Idade', 'name_key' => 'idade', 'required' => false, 'options_json' => null, 'sort_order' => 3],
            ['type' => 'radio', 'label' => 'Sexo', 'name_key' => 'sexo', 'required' => false, 'options_json' => json_encode(['options' => ['Feminino', 'Masculino', 'Outro']]), 'sort_order' => 4],
            ['type' => 'text', 'label' => 'CPF', 'name_key' => 'cpf', 'required' => false, 'options_json' => null, 'sort_order' => 5],
            ['type' => 'text', 'label' => 'RG', 'name_key' => 'rg', 'required' => false, 'options_json' => null, 'sort_order' => 6],
            ['type' => 'select', 'label' => 'Estado civil', 'name_key' => 'estado_civil', 'required' => false, 'options_json' => json_encode(['options' => ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União estável']]), 'sort_order' => 7],
            ['type' => 'text', 'label' => 'Profissão', 'name_key' => 'profissao', 'required' => false, 'options_json' => null, 'sort_order' => 8],
            ['type' => 'text', 'label' => 'Indicado por', 'name_key' => 'indicado_por', 'required' => false, 'options_json' => null, 'sort_order' => 9],
            ['type' => 'text', 'label' => 'Telefone / WhatsApp', 'name_key' => 'telefone_whatsapp', 'required' => false, 'options_json' => null, 'sort_order' => 10],
            ['type' => 'text', 'label' => 'Telefone alternativo', 'name_key' => 'telefone_alternativo', 'required' => false, 'options_json' => null, 'sort_order' => 11],
            ['type' => 'text', 'label' => 'E-mail', 'name_key' => 'email', 'required' => false, 'options_json' => null, 'sort_order' => 12],
            ['type' => 'text', 'label' => 'Endereço completo', 'name_key' => 'endereco_completo', 'required' => false, 'options_json' => null, 'sort_order' => 13],
            ['type' => 'text', 'label' => 'Bairro', 'name_key' => 'bairro', 'required' => false, 'options_json' => null, 'sort_order' => 14],
            ['type' => 'text', 'label' => 'Cidade', 'name_key' => 'cidade', 'required' => false, 'options_json' => null, 'sort_order' => 15],
            ['type' => 'text', 'label' => 'CEP', 'name_key' => 'cep', 'required' => false, 'options_json' => null, 'sort_order' => 16],
            ['type' => 'checkbox', 'label' => 'Instagram', 'name_key' => 'conheceu_instagram', 'required' => false, 'options_json' => null, 'sort_order' => 17],
            ['type' => 'checkbox', 'label' => 'Google', 'name_key' => 'conheceu_google', 'required' => false, 'options_json' => null, 'sort_order' => 18],
            ['type' => 'checkbox', 'label' => 'Facebook', 'name_key' => 'conheceu_facebook', 'required' => false, 'options_json' => null, 'sort_order' => 19],
            ['type' => 'checkbox', 'label' => 'Indicação de amigo/familiar', 'name_key' => 'conheceu_indicacao_amigo', 'required' => false, 'options_json' => null, 'sort_order' => 20],
            ['type' => 'checkbox', 'label' => 'Indicação médica', 'name_key' => 'conheceu_indicacao_medica', 'required' => false, 'options_json' => null, 'sort_order' => 21],
            ['type' => 'checkbox', 'label' => 'Plano de saúde', 'name_key' => 'conheceu_plano_saude', 'required' => false, 'options_json' => null, 'sort_order' => 22],
            ['type' => 'text', 'label' => 'Outro (como nos conheceu)', 'name_key' => 'conheceu_outro', 'required' => false, 'options_json' => null, 'sort_order' => 23],
            ['type' => 'radio', 'label' => 'Possui plano de saúde?', 'name_key' => 'possui_plano_saude', 'required' => false, 'options_json' => json_encode(['options' => ['Sim', 'Não']]), 'sort_order' => 24],
            ['type' => 'text', 'label' => 'Operadora', 'name_key' => 'operadora_plano', 'required' => false, 'options_json' => null, 'sort_order' => 25],
            ['type' => 'text', 'label' => 'Número da carteirinha', 'name_key' => 'numero_carteirinha', 'required' => false, 'options_json' => null, 'sort_order' => 26],
            ['type' => 'checkbox', 'label' => 'Aceito receber comunicações e promoções por WhatsApp/e-mail', 'name_key' => 'lgpd_comunicacoes', 'required' => false, 'options_json' => null, 'sort_order' => 27],
            ['type' => 'checkbox', 'label' => 'Aceito receber lembretes de consulta', 'name_key' => 'lgpd_lembretes', 'required' => false, 'options_json' => null, 'sort_order' => 28],
            ['type' => 'signature', 'label' => 'Assinatura do paciente', 'name_key' => 'assinatura_paciente', 'required' => false, 'options_json' => null, 'sort_order' => 29],
            ['type' => 'signature', 'label' => 'Responsável legal (se menor)', 'name_key' => 'assinatura_responsavel_legal', 'required' => false, 'options_json' => null, 'sort_order' => 30],
        ];

        foreach ($templateIds as $templateId) {
            DB::table('form_fields')->where('template_id', $templateId)->delete();
            $rows = array_map(function (array $f) use ($templateId, $now): array {
                return array_merge($f, [
                    'template_id' => $templateId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }, $fields);
            DB::table('form_fields')->insert($rows);
        }
    }

    public function down(): void
    {
        // Migração de refresh; sem rollback automático para não perder customizações manuais.
    }
};
