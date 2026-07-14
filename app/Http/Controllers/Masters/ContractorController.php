<?php
/**
 * File: app/Http/Controllers/Masters/ContractorController.php
 * Purpose: CRUD and management for Contractors — labour assignment, contractor-wise attendance and payroll views.
 *          Branch-wise scoped throughout: a Contractor belongs to a branch
 *          (branch_id, additive column — see 2026_07_12_000001 migration), and
 *          the labour/attendance/payroll sub-views were already scoped via the
 *          linked Employee's branch_id.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\PayrollRecord;
use App\Support\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContractorController extends Controller
{
    public function index(Request $request)
    {
        $query = BranchScope::scopeQuery(Contractor::query())->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('code', 'like', $s)
                ->orWhere('contact_person', 'like', $s));
        }

        $contractors = $query->paginate(20)->withQueryString();

        // FSD 9.1 — "system shall warn users before contractor agreement or
        // licence expiry" — a lightweight summary banner, computed over the
        // branch-scoped active set (not just the current page).
        $expiringSoonCount = BranchScope::scopeQuery(Contractor::where('is_active', true))
            ->where(function ($q) {
                $soon = now()->addDays(30)->toDateString();
                $q->whereBetween('license_expiry', [now()->toDateString(), $soon])
                    ->orWhereBetween('agreement_end_date', [now()->toDateString(), $soon]);
            })->count();

        return view('masters.contractors.index', compact('contractors', 'expiringSoonCount'));
    }

    private function formOptions(): array
    {
        return [
            // The owning branch (`branch_id`) is always the currently active
            // one — store()/update() force it via BranchScope::stampBranchId()
            // regardless of what's submitted, so it's shown read-only.
            'currentBranch' => BranchScope::currentBranch(),
            // `allBranches` remains the full list for the separate Branch
            // Applicability multi-select (`branch_ids[]`) — a deliberate,
            // multi-branch feature (a contractor's labour can work across
            // several branches), unrelated to the single owning branch above.
            'allBranches' => Branch::active()->orderBy('name')->get(),
            'states' => config('states', []),
        ];
    }

    public function create()
    {
        return view('masters.contractors.create', $this->formOptions());
    }

    private function rules(?int $contractorId = null): array
    {
        return [
            'name'           => ['required', 'string', 'max:100', Rule::unique('contractors', 'name')->ignore($contractorId)],
            'company_name'   => ['nullable', 'string', 'max:150'],
            'code'           => ['required', 'string', 'max:20', Rule::unique('contractors', 'code')->ignore($contractorId)],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone'          => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'alternate_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'email'          => ['nullable', 'email', 'max:150'],
            'address'        => ['required', 'string'],
            'state'          => ['required', 'string', Rule::in(config('states', []))],
            'district'       => ['required', 'string', 'max:100'],
            'pincode'        => ['required', 'digits:6'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['required_with:license_number', 'nullable', 'date'],
            'gst_number'     => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan_number'     => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'pf_registration_number'  => ['nullable', 'string', 'max:50'],
            'esi_registration_number' => ['nullable', 'string', 'max:50'],
            'agreement_start_date' => ['required', 'date'],
            'agreement_end_date'   => ['nullable', 'date', 'after:agreement_start_date'],
            'max_labour_count'     => ['nullable', 'integer', 'min:0'],
            'branch_id'      => ['nullable', 'exists:branches,id'],
            'branch_ids'     => ['required', 'array', 'min:1'],
            'branch_ids.*'   => ['exists:branches,id'],
            'is_active'      => ['boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'gst_number.regex' => 'The GST number format is invalid.',
            'pan_number.regex' => 'The PAN number format is invalid (e.g. ABCDE1234F).',
            'phone.regex' => 'The mobile number format is invalid.',
            'alternate_phone.regex' => 'The alternate number format is invalid.',
            'license_expiry.required_with' => 'Licence expiry date is required when a licence number is entered.',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $data = BranchScope::stampBranchId($data);
        BranchScope::assertBranchIsActive($data['branch_id'] ?? null);

        // The primary/owning branch always counts as applicable, even if the
        // user didn't explicitly tick it in the multi-select.
        if (! empty($data['branch_id']) && ! in_array($data['branch_id'], $branchIds)) {
            $branchIds[] = $data['branch_id'];
        }

        $contractor = Contractor::create($data);
        $contractor->branches()->sync($branchIds);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor created successfully.');
    }

    public function edit(Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);
        $contractor->load(['branches', 'documents']);
        $options = $this->formOptions();
        $options['currentBranch'] = $contractor->branch ?? $options['currentBranch'];
        return view('masters.contractors.edit', array_merge(compact('contractor'), $options));
    }

    public function update(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $data = $request->validate($this->rules($contractor->id), $this->messages());
        $branchIds = $data['branch_ids'];
        unset($data['branch_ids']);

        $data = BranchScope::stampBranchId($data);

        if (! empty($data['branch_id']) && ! in_array($data['branch_id'], $branchIds)) {
            $branchIds[] = $data['branch_id'];
        }

        $contractor->update($data);
        $contractor->branches()->sync($branchIds);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor updated successfully.');
    }

    public function destroy(Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        // FSD 9.1 — "A contractor with active Contract Labour shall not be
        // deleted." Checked across BOTH contract-labour mechanisms this app
        // uses (Employee.contractor_id and the separate ContractWorker
        // model) — previously only the Employee side was guarded.
        if ($contractor->hasActiveContractLabour()) {
            return back()->with('error', 'Cannot delete contractor with active contract labour assigned.');
        }
        $contractor->delete();
        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor deleted successfully.');
    }

    // ── Documents ─────────────────────────────────────────────────────────

    public function uploadDocument(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $request->validate([
            'document_type' => ['required', 'in:agreement,licence,other'],
            'file'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('file')->store('contractor-documents/' . $contractor->id, 'public');

        $contractor->documents()->create([
            'document_type' => $request->document_type,
            'original_name' => $request->file('file')->getClientOriginalName(),
            'file_path'     => $path,
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function deleteDocument(Contractor $contractor, ContractorDocument $document)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        if ($document->contractor_id !== $contractor->id) {
            abort(404);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document removed successfully.');
    }

    // ── Contract Labour Assignment (standalone page) ────────────────────

    /**
     * Standalone contractor selector page — mirrors contract-attendance UI pattern.
     */
    public function contractLabourIndex(Request $request)
    {
        $contractors = BranchScope::scopeQuery(Contractor::where('is_active', true))->orderBy('name')->get(['id', 'name', 'code']);

        $contractor          = null;
        $employees           = collect();
        $unassignedEmployees = collect();

        if ($request->filled('contractor_id')) {
            $contractor = Contractor::findOrFail($request->contractor_id);
            BranchScope::assertBranchAccess($contractor->branch_id);

            $employees = BranchScope::scopeQuery($contractor->employees())
                ->with(['department', 'designation'])
                ->orderBy('first_name')
                ->paginate(20)
                ->withQueryString();

            $unassignedEmployees = BranchScope::scopeQuery(
                Employee::active()->whereNull('contractor_id')
            )
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'employee_code', 'branch_id']);
        }

        return view('contract-labour.index', compact('contractors', 'contractor', 'employees', 'unassignedEmployees'));
    }

    // ── Contractor Labour Management ─────────────────────────────────────

    /**
     * Show all employees (labour) assigned to a specific contractor.
     */
    public function labour(Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $employees = BranchScope::scopeQuery($contractor->employees())
            ->with(['department', 'designation', 'shift'])
            ->orderBy('first_name')
            ->paginate(20);

        $unassignedEmployees = BranchScope::scopeQuery(
            Employee::active()->whereNull('contractor_id')
        )
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'employee_code', 'branch_id']);

        return view('masters.contractors.labour.index', compact('contractor', 'employees', 'unassignedEmployees'));
    }

    /**
     * Assign an employee to this contractor.
     */
    public function assignLabour(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        // FSD 9.1 — "Contract Labour shall not be assigned to an inactive contractor."
        if (! $contractor->is_active) {
            return back()->with('error', 'Cannot assign labour to an inactive contractor.');
        }

        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
        ]);

        $employee = Employee::findOrFail($request->employee_id);
        BranchScope::assertBranchAccess($employee->branch_id);

        if ($employee->contractor_id) {
            return back()->with('error', 'Employee is already assigned to a contractor.');
        }

        $employee->update(['contractor_id' => $contractor->id]);

        return back()->with('success', 'Employee assigned to contractor successfully.');
    }

    /**
     * Remove an employee from this contractor.
     */
    public function removeLabour(Contractor $contractor, Employee $employee)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);
        BranchScope::assertBranchAccess($employee->branch_id);

        if ($employee->contractor_id !== $contractor->id) {
            return back()->with('error', 'Employee is not assigned to this contractor.');
        }

        $employee->update(['contractor_id' => null]);

        return back()->with('success', 'Employee removed from contractor successfully.');
    }

    // ── Contractor-wise Attendance ───────────────────────────────────────

    /**
     * View attendance filtered by contractor.
     */
    public function attendance(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $date = $request->input('date', now()->toDateString());

        $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');

        $query = Attendance::with(['employee.department'])
            ->whereIn('employee_id', $employeeIds)
            ->where('date', $date)
            ->orderBy('in_time');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendance  = $query->paginate(25)->withQueryString();
        $summary     = Attendance::whereIn('employee_id', $employeeIds)
            ->where('date', $date)
            ->selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('masters.contractors.labour.attendance', compact('contractor', 'attendance', 'summary', 'date'));
    }

    // ── Contractor-wise Payroll ──────────────────────────────────────────

    /**
     * View payroll records filtered by contractor.
     */
    public function payroll(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $employeeIds = BranchScope::scopeQuery($contractor->employees())->pluck('id');

        $records = PayrollRecord::with(['employee.department'])
            ->whereIn('employee_id', $employeeIds)
            ->where('month', $month)->where('year', $year)
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate(25)->withQueryString();

        $summary = PayrollRecord::whereIn('employee_id', $employeeIds)
            ->where('month', $month)->where('year', $year)
            ->selectRaw('COUNT(*) as count, SUM(gross_earnings) as gross, SUM(net_salary) as net, SUM(total_deductions) as deductions')
            ->first();

        return view('masters.contractors.labour.payroll', compact('contractor', 'records', 'summary', 'month', 'year'));
    }
}
