<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\EarningsComponent;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EarningsComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = EarningsComponent::orderBy('sort_order');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)->orWhere('code', 'like', $s));
        }

        $components = $query->paginate(20)->withQueryString();
        return view('masters.earnings.index', compact('components'));
    }

    public function create()
    {
        return view('masters.earnings.create');
    }

    /**
     * Earnings Component only collects Name + Percentage + Status now —
     * Code is auto-generated (see createWithGeneratedCode(), mirrors
     * BranchController/ShiftController's own pattern) and Type always
     * defaults to 'percentage' since that's the only calculation mode this
     * simplified form supports. Calculation Base/Taxable/PF/ESI/Sort Order
     * keep their DB column defaults, un-set by the form.
     */
    private function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active'  => ['required', 'boolean'],
        ];
    }

    /**
     * Generates the next Earnings Component Code (one higher than the
     * latest existing code, preserving its prefix/padding) and creates the
     * component with it — mirrors BranchController::createWithGeneratedCode()
     * exactly. A row lock on the latest component serializes concurrent
     * creations; the retry loop is a defensive fallback against the rare
     * duplicate-key race the lock doesn't cover — the unique index on
     * `code` is the actual guarantee against ever storing a collision.
     */
    private function createWithGeneratedCode(array $data): EarningsComponent
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $lastCode = EarningsComponent::orderByDesc('id')->lockForUpdate()->value('code');
                    $data['code'] = SequentialCodeGenerator::next($lastCode, 'EC0001');
                    $data['type'] = 'percentage';

                    return EarningsComponent::create($data);
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 5) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Unable to generate a unique Earnings Component Code after several attempts.');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        $this->createWithGeneratedCode($data);

        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component created successfully.');
    }

    public function edit(EarningsComponent $earningsComponent)
    {
        return view('masters.earnings.edit', compact('earningsComponent'));
    }

    public function update(Request $request, EarningsComponent $earningsComponent)
    {
        $data = $request->validate($this->rules());

        $earningsComponent->update($data);

        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component updated successfully.');
    }

    public function destroy(EarningsComponent $earningsComponent)
    {
        $earningsComponent->delete();
        return redirect()->route('masters.earnings.index')
            ->with('success', 'Earnings component deleted successfully.');
    }
}
