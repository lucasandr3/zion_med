<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\DemonstrationRequest;
use App\Models\User;
use App\Notifications\NovoLeadPlataforma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class DemonstracaoController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'clinic' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Informe seu nome.',
            'clinic.required' => 'Informe o nome da clínica.',
            'email.required' => 'Informe seu e-mail.',
            'phone.required' => 'Informe seu WhatsApp.',
        ]);

        $lead = DemonstrationRequest::create($validated);

        $admins = User::where('role', Role::PlatformAdmin)->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NovoLeadPlataforma($lead));
        }

        $message = 'Solicitação enviada! Entraremos em contato em breve para agendar sua demonstração.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('home')
            ->with('demonstracao_sucesso', $message);
    }
}
