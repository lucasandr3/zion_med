<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(): View
    {
        $plans = Plan::orderBy('sort_order')->orderBy('key')->get();
        $trialDays = (int) config('asaas.trial_days', 14);

        return view('platform.plans.index', [
            'plans' => $plans,
            'trialDays' => $trialDays,
        ]);
    }

    public function create(): View
    {
        $nextOrder = (int) Plan::max('sort_order') + 1;

        return view('platform.plans.edit', [
            'plan' => null,
            'sortOrder' => $nextOrder,
        ]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        Plan::create([
            'key' => $request->input('key'),
            'name' => $request->input('name'),
            'value' => $request->input('value'),
            'description' => $request->input('description'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('platform.plans.index')->with('success', 'Plano criado.');
    }

    public function edit(Plan $plan): View
    {
        return view('platform.plans.edit', [
            'plan' => $plan,
            'sortOrder' => $plan->sort_order,
        ]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update([
            'name' => $request->input('name'),
            'value' => $request->input('value'),
            'description' => $request->input('description'),
            'sort_order' => (int) $request->input('sort_order', 0),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('platform.plans.index')->with('success', 'Plano atualizado.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('platform.plans.index')->with('success', 'Plano removido.');
    }
}
