<?php
/**
 * File: app/Http/Controllers/Masters/ContractorController.php
 * Purpose: CRUD and management for Contractors — labour assignment, contractor-wise attendance and payroll views.
 *          Contractor is a single, global master (no branch scoping). The
 *          labour/attendance/payroll sub-views remain scoped via the linked
 *          Employee's own branch_id, independent of the contractor.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PayrollRecord;
use App\Support\BranchScope;
use App\Support\SequentialCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ContractorController extends Controller
{
    public function index(Request $request)
    {
        $query = Contractor::query()->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('code', 'like', $s)
                ->orWhere('contact_person', 'like', $s));
        }

        $contractors = $query->paginate(20)->withQueryString();

        // FSD 9.1 — "system shall warn users before contractor agreement or
        // licence expiry" — a lightweight summary banner over the active set.
        $expiringSoonCount = Contractor::where('is_active', true)
            ->where(function ($q) {
                $soon = now()->addDays(30)->toDateString();
                $q->whereBetween('license_expiry', [now()->toDateString(), $soon])
                    ->orWhereBetween('agreement_end_date', [now()->toDateString(), $soon]);
            })->count();

        return view('masters.contractors.index', compact('contractors', 'expiringSoonCount'));
    }

    private function formOptions(): array
    {
        return ['states' => config('states', [])];
    }

    public function create()
    {
        return view('masters.contractors.create', $this->formOptions());
    }

    private function rules(?int $contractorId = null): array
    {
        return [
            'name'           => ['required', 'string', 'max:100', Rule::unique('contractors', 'name')->ignore($contractorId)],
            // `code` is auto-generated on create (see createWithGeneratedCode())
            // and never accepted from the client there; still required/unique/
            // editable on update, preserving today's Edit behavior.
            'code'           => $contractorId
                ? ['required', 'string', 'max:20', Rule::unique('contractors', 'code')->ignore($contractorId)]
                : ['nullable', 'string', 'max:20'],
            'contact_person' => ['required', 'string', 'max:100'],
            'phone'          => ['required', 'digits:10'],
            'alternate_phone' => ['nullable', 'digits:10'],
            'email'          => ['nullable', 'email', 'max:150'],
            // Address is optional; State/District/PIN Code are only required
            // when an Address has actually been entered.
            'address'        => ['nullable', 'string'],
            'state'          => ['nullable', 'string', 'required_with:address', Rule::in(config('states', []))],
            'district'       => ['nullable', 'string', 'max:100', 'required_with:address'],
            'pincode'        => ['nullable', 'digits:6', 'required_with:address'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_expiry' => ['required_with:license_number', 'nullable', 'date'],
            'gst_number'     => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan_number'     => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'pf_registration_number'  => ['nullable', 'string', 'max:50'],
            'esi_registration_number' => ['nullable', 'string', 'max:50'],
            'agreement_start_date' => ['required', 'date'],
            'agreement_end_date'   => ['nullable', 'date', 'after_or_equal:agreement_start_date'],
            'max_labour_count'     => ['nullable', 'integer', 'min:0'],
            'is_active'      => ['required', 'boolean'],
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
            'state.required_with' => 'The state field is required when an address is entered.',
            'district.required_with' => 'The district field is required when an address is entered.',
            'pincode.required_with' => 'The PIN code field is required when an address is entered.',
        ];
    }

    /**
     * Generates the next Contractor Code (one higher than the latest
     * existing code, preserving its prefix/padding) and creates the
     * contractor with it — mirrors BranchController::createWithGeneratedCode().
     */
    private function createWithGeneratedCode(array $data): Contractor
    {
        // See ShiftController::createWithGeneratedCode() for why this needs
        // more than a couple of retries plus a jittered backoff: two
        // near-simultaneous submissions can both read the same "last code"
        // and race for the same next value.
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            try {
                return DB::transaction(function () use ($data) {
                    $lastCode = Contractor::orderByDesc('id')->lockForUpdate()->value('code');
                    $data['code'] = SequentialCodeGenerator::next($lastCode, 'CN0001');

                    return Contractor::create($data);
                });
            } catch (QueryException $e) {
                $isDuplicate = (string) $e->getCode() === '23000';
                if (! $isDuplicate || $attempt === 10) {
                    throw $e;
                }
                usleep(random_int(20_000, 80_000));
            }
        }

        throw new \RuntimeException('Unable to generate a unique Contractor Code after several attempts.');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());
        unset($data['code']);

        $contractor = $this->createWithGeneratedCode($data);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor created successfully.');
    }

    public function edit(Contractor $contractor)
    {
        $contractor->load('documents');
        return view('masters.contractors.edit', array_merge(compact('contractor'), $this->formOptions()));
    }

    public function update(Request $request, Contractor $contractor)
    {
        $data = $request->validate($this->rules($contractor->id), $this->messages());
        $contractor->update($data);

        return redirect()->route('masters.contractors.index')
            ->with('success', 'Contractor updated successfully.');
    }

    public function destroy(Contractor $contractor)
    {
        // FSD 9.1 — "A contractor with active Contract Labour shall not be
        // deleted." Checked across BOTH contract-labour mechanisms this app
        // uses (Employee.contractor_id and the separate ContractWorker model).
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
        $contractors = Contractor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']);

        $contractor          = null;
        $employees           = collect();
        $unassignedEmployees = collect();

        if ($request->filled('contractor_id')) {
            $contractor = Contractor::findOrFail($request->contractor_id);

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
     * Show all employees (labour) assigned to a specific contractor. Still
     * scoped to the viewer's own branch via the Employee records themselves
     * (BranchScope::scopeQuery) — independent of the (now branch-agnostic)
     * Contractor entity.
     */
    public function labour(Contractor $contractor)
    {
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
