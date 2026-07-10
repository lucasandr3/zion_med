<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

/**
 * Quiz curto de compreensão clínica (TCLE) — configurável no modelo.
 *
 * Formato interno:
 * [
 *   ['id' => 'q1', 'prompt' => '...', 'options' => ['Sim', 'Não'], 'correct_index' => 0],
 * ]
 */
class ComprehensionQuizService
{
    public const MAX_QUESTIONS = 5;

    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 4;

    /**
     * @param  mixed  $raw
     * @return list<array{id: string, prompt: string, options: list<string>, correct_index: int}>
     */
    public function normalize(mixed $raw): array
    {
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $out = [];
        foreach (array_slice(array_values($raw), 0, self::MAX_QUESTIONS) as $i => $item) {
            if (! is_array($item)) {
                continue;
            }
            $prompt = trim((string) ($item['prompt'] ?? ''));
            $optionsRaw = $item['options'] ?? [];
            if ($prompt === '' || ! is_array($optionsRaw)) {
                continue;
            }
            $options = [];
            foreach (array_slice(array_values($optionsRaw), 0, self::MAX_OPTIONS) as $opt) {
                $label = trim((string) $opt);
                if ($label !== '') {
                    $options[] = $label;
                }
            }
            if (count($options) < self::MIN_OPTIONS) {
                continue;
            }
            $correct = (int) ($item['correct_index'] ?? 0);
            if ($correct < 0 || $correct >= count($options)) {
                $correct = 0;
            }
            $id = trim((string) ($item['id'] ?? ''));
            if ($id === '') {
                $id = 'q'.($i + 1);
            }
            $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $id) ?: ('q'.($i + 1));
            $out[] = [
                'id' => mb_substr($id, 0, 40),
                'prompt' => mb_substr($prompt, 0, 500),
                'options' => $options,
                'correct_index' => $correct,
            ];
        }

        return $out;
    }

    /**
     * Versão pública (sem gabarito).
     *
     * @param  list<array{id: string, prompt: string, options: list<string>, correct_index: int}>  $quiz
     * @return list<array{id: string, prompt: string, options: list<string>}>
     */
    public function forPublic(array $quiz): array
    {
        return array_map(static fn (array $q) => [
            'id' => $q['id'],
            'prompt' => $q['prompt'],
            'options' => $q['options'],
        ], $quiz);
    }

    /**
     * Valida respostas do paciente. Lança ValidationException se incompleto ou incorreto.
     *
     * @param  list<array{id: string, prompt: string, options: list<string>, correct_index: int}>  $quiz
     * @param  mixed  $answers  mapa id => índice escolhido
     * @return array{passed: bool, answers: array<string, int>, detail: list<array{id: string, selected_index: int, correct: bool}>}
     */
    public function assertPassed(array $quiz, mixed $answers): array
    {
        if ($quiz === []) {
            return ['passed' => true, 'answers' => [], 'detail' => []];
        }

        if (! is_array($answers)) {
            throw ValidationException::withMessages([
                '_comprehension_quiz' => ['Responda às perguntas de compreensão antes de enviar.'],
            ]);
        }

        $detail = [];
        $normalizedAnswers = [];
        $allCorrect = true;

        foreach ($quiz as $q) {
            $id = $q['id'];
            if (! array_key_exists($id, $answers)) {
                throw ValidationException::withMessages([
                    '_comprehension_quiz' => ['Responda todas as perguntas de compreensão antes de enviar.'],
                ]);
            }
            $selected = (int) $answers[$id];
            $correct = $selected === (int) $q['correct_index'];
            if (! $correct) {
                $allCorrect = false;
            }
            $normalizedAnswers[$id] = $selected;
            $detail[] = [
                'id' => $id,
                'selected_index' => $selected,
                'correct' => $correct,
            ];
        }

        if (! $allCorrect) {
            throw ValidationException::withMessages([
                '_comprehension_quiz' => ['Uma ou mais respostas estão incorretas. Revise o termo e tente novamente.'],
            ]);
        }

        return [
            'passed' => true,
            'answers' => $normalizedAnswers,
            'detail' => $detail,
        ];
    }
}
