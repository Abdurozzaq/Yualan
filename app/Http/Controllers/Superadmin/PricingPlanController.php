<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Str;

class PricingPlanController extends Controller
{
    public function index(Request $request): Response
    {

        $perPage = (int) $request->input('perPage', 10);
        $sortBy = $request->input('sortBy', 'plan_name');
        $sortDirection = $request->input('sortDirection', 'asc');
        $search = $request->input('search');
        $filterField = $request->input('filterField');

        $query = PricingPlan::query();

        if ($search) {
            $query->where(function ($q) use ($search, $filterField) {
                $fields = in_array($filterField, ['plan_name', 'plan_description', 'period_type'])
                    ? [$filterField]
                    : ['plan_name', 'plan_description', 'period_type'];
                foreach ($fields as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        $query->orderBy($sortBy, $sortDirection);

        $plans = $query->paginate($perPage)->withQueryString();

        return Inertia::render('superadmin/pricing/Index', [
            'plans' => $plans,
            'filters' => [
                'perPage' => $perPage,
                'sortBy' => $sortBy,
                'sortDirection' => $sortDirection,
                'search' => $search,
                'filterField' => $filterField,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {

        $validated = $request->validate([
            'plan_name' => ['required', 'string', 'max:255', Rule::unique('pricing_plans', 'plan_name')],
            'plan_description' => ['nullable', 'string', 'max:1000'],
            // Tambah opsi lifetime untuk langganan seumur hidup
            'period_type' => ['required', Rule::in(['monthly', 'quarterly', 'yearly', 'lifetime'])],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        PricingPlan::create([
            'id' => (string) Str::uuid(),
            'plan_name' => $validated['plan_name'],
            'plan_description' => $validated['plan_description'] ?? null,
            'period_type' => $validated['period_type'],
            'price' => $validated['price'],
            'discount_percentage' => $validated['discount_percentage'] ?? 0,
        ]);

        return redirect()->route('superadmin.pricing.index')->with('success', 'Pricing plan created.');
    }

    public function update(Request $request, PricingPlan $pricing): RedirectResponse
    {

        $validated = $request->validate([
            'plan_name' => ['required', 'string', 'max:255', Rule::unique('pricing_plans', 'plan_name')->ignore($pricing->id)],
            'plan_description' => ['nullable', 'string', 'max:1000'],
            // Tambah opsi lifetime untuk langganan seumur hidup
            'period_type' => ['required', Rule::in(['monthly', 'quarterly', 'yearly', 'lifetime'])],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $pricing->update($validated);

        return redirect()->route('superadmin.pricing.index')->with('success', 'Pricing plan updated.');
    }

    public function destroy(PricingPlan $pricing): RedirectResponse
    {
        $pricing->delete();
        return redirect()->route('superadmin.pricing.index')->with('success', 'Pricing plan deleted.');
    }
}
