<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeType;
use App\Models\Contractor;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with(['branch', 'department', 'designation', 'employeeType'])
            ->orderBy('first_name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('employee_code', 'like', $s)
                ->orWhere('official_email', 'like', $s));
        }

        if ($request->filled('department_id')) $query->where('department_id', $request->department_id);
        if ($request->filled('branch_id'))     $query->where('branch_id', $request->branch_id);
        if ($request->filled('status'))        $query->where('status', $request->status);

        $employees   = $query->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();
        $branches    = Branch::orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments', 'branches'));
    }

    public function create()
    {
        return view('employees.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['created_by'] = auth()->id();

        $employee = DB::transaction(function () use ($data, $request) {
            $emp = Employee::create($data);

            // Create login user if email provided
            if ($request->filled('official_email') && $request->boolean('create_user')) {
                User::create([
                    'name'        => $emp->full_name,
                    'email'       => $emp->official_email,
                    'password'    => Hash::make('Welcome@' . now()->year),
                    'role_id'     => $request->input('role_id', 2),
                    'employee_id' => $emp->id,
                    'is_active'   => true,
                ]);
            }

            return $emp;
        });

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['branch', 'department', 'designation', 'employeeType', 'contractor',
            'shift', 'reportingTo', 'user', 'bankDetails', 'currentSalary', 'exitRecord']);
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', array_merge(compact('employee'), $this->formData()));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate($this->rules($employee->id));
        $employee->update($data);
        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->update(['status' => 'terminated']);
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee removed.');
    }

    public function documents(Employee $employee)
    {
        $documents = $employee->documents()->latest()->get();
        return view('employees.documents', compact('employee', 'documents'));
    }

    public function uploadDocument(Request $request, Employee $employee)
    {
        $request->validate([
            'document_type'   => ['required', 'string'],
            'document_number' => ['nullable', 'string'],
            'file'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('file')->store('employee-documents/' . $employee->id, 'public');
        $employee->documents()->create([
            'document_type'   => $request->document_type,
            'document_number' => $request->document_number,
            'file_path'       => $path,
        ]);

        return back()->with('success', 'Document uploaded.');
    }

    public function salary(Employee $employee)
    {
        $salary  = $employee->currentSalary;
        $history = $employee->salaryHistory()->with('slab')->get();
        $slabs   = \App\Models\SalarySlab::where('is_active', true)->get();
        return view('employees.salary', compact('employee', 'salary', 'history', 'slabs'));
    }

    public function storeSalary(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'slab_id'          => ['required', 'exists:salary_slabs,id'],
            'ctc'              => ['required', 'numeric', 'min:0'],
            'basic'            => ['required', 'numeric', 'min:0'],
            'hra'              => ['required', 'numeric', 'min:0'],
            'other_allowances' => ['required', 'numeric', 'min:0'],
            'effective_from'   => ['required', 'date'],
            'pf_applicable'    => ['boolean'],
            'esi_applicable'   => ['boolean'],
        ]);
        $data['employee_id'] = $employee->id;
        $data['created_by']  = auth()->id();

        $employee->salaryHistory()->update(['is_current' => false]);
        $employee->salaryHistory()->create(array_merge($data, ['is_current' => true]));

        return back()->with('success', 'Salary structure saved.');
    }

    public function exit(Employee $employee)
    {
        return view('employees.exit', compact('employee'));
    }

    public function processExit(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'exit_type'         => ['required', 'in:resignation,termination,retirement,absconding'],
            'resignation_date'  => ['required', 'date'],
            'last_working_date' => ['required', 'date'],
            'exit_reason'       => ['required', 'string'],
            'remarks'           => ['nullable', 'string'],
        ]);
        $data['approved_by'] = auth()->id();

        $employee->exitRecord()->updateOrCreate(['employee_id' => $employee->id], $data);
        $employee->update(['status' => 'terminated']);

        return redirect()->route('employees.index')->with('success', 'Employee exit processed.');
    }

    private function formData(): array
    {
        return [
            'branches'      => Branch::active()->orderBy('name')->get(),
            'departments'   => Department::orderBy('name')->get(),
            'designations'  => Designation::orderBy('name')->get(),
            'employeeTypes' => EmployeeType::where('is_active', true)->get(),
            'contractors'   => Contractor::where('is_active', true)->orderBy('name')->get(),
            'shifts'        => Shift::where('is_active', true)->get(),
            'managers'      => Employee::active()->orderBy('first_name')->get(),
            'roles'         => \App\Models\Role::orderBy('name')->get(),
        ];
    }

    private function rules(int $excludeId = 0): array
    {
        return [
            'first_name'       => ['required', 'string', 'max:100'],
            'last_name'        => ['required', 'string', 'max:100'],
            'employee_code'    => ['required', 'string', 'max:20', 'unique:employees,employee_code,' . $excludeId],
            'branch_id'        => ['required', 'exists:branches,id'],
            'department_id'    => ['required', 'exists:departments,id'],
            'designation_id'   => ['required', 'exists:designations,id'],
            'employee_type_id' => ['required', 'exists:employee_types,id'],
            'date_of_joining'  => ['required', 'date'],
            'date_of_birth'    => ['required', 'date'],
            'gender'           => ['required', 'in:male,female,other'],
            'official_email'   => ['required', 'email', 'max:255', 'unique:employees,official_email,' . $excludeId],
            'phone'            => ['required', 'string', 'max:20'],
            'status'           => ['required', 'in:active,inactive,probation,terminated'],
            'shift_id'         => ['nullable', 'exists:shifts,id'],
            'reporting_to'     => ['nullable', 'exists:employees,id'],
            'address_line1'    => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'pincode'          => ['nullable', 'string', 'max:10'],
            'aadhaar_number'   => ['nullable', 'string', 'max:20'],
            'pan_number'       => ['nullable', 'string', 'max:20'],
            'is_pf_applicable' => ['boolean'],
            'is_esi_applicable'=> ['boolean'],
            'is_tds_applicable'=> ['boolean'],
        ];
    }
}
