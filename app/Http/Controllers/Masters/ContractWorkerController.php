<?php
/**
 * File: app/Http/Controllers/Masters/ContractWorkerController.php
 * Purpose: CRUD management for contract workers under a specific contractor.
 * Author: System
 * Date: 2026-07-01
 */

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Contractor;
use App\Models\ContractWorker;
use App\Support\BranchScope;
use Illuminate\Http\Request;

class ContractWorkerController extends Controller
{
    public function index(Contractor $contractor, Request $request)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $query = $contractor->contractWorkers()->orderBy('name');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('name', 'like', $s)
                ->orWhere('phone', 'like', $s)
                ->orWhere('skill_type', 'like', $s));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $workers = $query->paginate(20)->withQueryString();

        return view('masters.contractors.workers.index', compact('contractor', 'workers'));
    }

    public function create(Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);
        return view('masters.contractors.workers.create', compact('contractor'));
    }

    public function store(Request $request, Contractor $contractor)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'id_proof_type'   => ['nullable', 'in:aadhaar,passport,voter_id,driving_license,other'],
            'id_proof_number' => ['nullable', 'string', 'max:50'],
            'skill_type'      => ['nullable', 'string', 'max:100'],
            'wage_type'       => ['required', 'in:daily,monthly'],
            'wage_amount'     => ['required', 'numeric', 'min:0'],
            'joining_date'    => ['nullable', 'date'],
            'status'          => ['required', 'in:active,inactive,terminated'],
        ]);

        $data['contractor_id'] = $contractor->id;
        ContractWorker::create($data);

        return redirect()->route('masters.contractors.workers.index', $contractor)
            ->with('success', 'Contract worker added successfully.');
    }

    public function edit(Contractor $contractor, ContractWorker $contractWorker)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);
        return view('masters.contractors.workers.edit', compact('contractor', 'contractWorker'));
    }

    public function update(Request $request, Contractor $contractor, ContractWorker $contractWorker)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'gender'          => ['nullable', 'in:male,female,other'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'id_proof_type'   => ['nullable', 'in:aadhaar,passport,voter_id,driving_license,other'],
            'id_proof_number' => ['nullable', 'string', 'max:50'],
            'skill_type'      => ['nullable', 'string', 'max:100'],
            'wage_type'       => ['required', 'in:daily,monthly'],
            'wage_amount'     => ['required', 'numeric', 'min:0'],
            'joining_date'    => ['nullable', 'date'],
            'status'          => ['required', 'in:active,inactive,terminated'],
        ]);

        $contractWorker->update($data);

        return redirect()->route('masters.contractors.workers.index', $contractor)
            ->with('success', 'Contract worker updated successfully.');
    }

    public function destroy(Contractor $contractor, ContractWorker $contractWorker)
    {
        BranchScope::assertBranchAccess($contractor->branch_id);

        if ($contractWorker->payrollRecords()->exists()) {
            return back()->with('error', 'Cannot delete worker with existing payroll records.');
        }

        $contractWorker->delete();

        return back()->with('success', 'Contract worker removed successfully.');
    }
}
