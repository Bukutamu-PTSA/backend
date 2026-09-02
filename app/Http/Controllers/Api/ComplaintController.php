<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Http\Requests\UpdateComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Models\Company;
use App\Models\Complainant;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplaintController extends Controller
{
    public function summary()
    {
        $categories = ComplaintCategory::withCount('complaints')
            ->orderBy('id')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn (ComplaintCategory $category) => [
                'id' => $category->id,
                'category_code' => $category->category_code,
                'category_name' => $category->category_name,
                'count' => $category->complaints_count,
            ]);

        return response()->json([
            'data' => $categories,
            'meta' => [
                'total' => Complaint::count(),
                'uncategorized_count' => Complaint::whereNull('category_id')->count(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:DIPROSES,SELESAI',
            'category_id' => 'nullable|integer|exists:complaint_categories,id',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer|between:1900,2200',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|between:1,100',
        ]);

        $complaints = Complaint::with(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($term) {
                    $query->whereRaw('LOWER(ticket_number) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(description) LIKE ?', [$term])
                        ->orWhereHas('complainant', fn ($query) => $query
                            ->whereRaw('LOWER(nama_lengkap) LIKE ?', [$term])
                            ->orWhereRaw('LOWER(nik) LIKE ?', [$term]))
                        ->orWhereHas('company', fn ($query) => $query
                            ->whereRaw('LOWER(nama_perusahaan) LIKE ?', [$term]))
                        ->orWhereHas('category', fn ($query) => $query
                            ->whereRaw('LOWER(category_name) LIKE ?', [$term]));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->byStatus($status))
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->byCategory($categoryId))
            ->when($filters['month'] ?? null, fn ($query, $month) => $query->whereMonth('complaint_date', $month))
            ->when($filters['year'] ?? null, fn ($query, $year) => $query->whereYear('complaint_date', $year))
            ->latest('complaint_date')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ComplaintResource::collection($complaints);
    }

    public function store(StoreComplaintRequest $request)
    {
        $complaint = DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $complainant = Complainant::firstOrCreate(
                ['nik' => $validated['complainant']['nik']],
                $validated['complainant']
            );

            $company = Company::firstOrCreate(
                [
                    'nama_perusahaan' => $validated['company']['nama_perusahaan'],
                    'kota_kab' => $validated['company']['kota_kab'],
                ],
                $validated['company']
            );

            $complaint = Complaint::create([
                'complainant_id' => $complainant->id,
                'company_id' => $company->id,
                'category_id' => $validated['category_id'],
                'ticket_number' => $this->nextTicketNumber(),
                'description' => $validated['description'],
                'priority' => 'MEDIUM',
                'follow_up_date' => $validated['follow_up_date'] ?? null,
                'complaint_date' => $validated['complaint_date'] ?? now(),
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $complaint->addPhoto($file);
            }

            return $complaint;
        });

        return (new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments'])))
            ->additional(['success' => true, 'message' => 'Aduan berhasil dibuat'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Complaint $complaint)
    {
        return new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments', 'updates']));
    }

    public function update(UpdateComplaintRequest $request, Complaint $complaint)
    {
        $oldStatus = $complaint->status;

        $complaint->update($request->validated());

        if ($complaint->wasChanged('status')) {
            $complaint->updates()->create([
                'old_status' => $oldStatus,
                'new_status' => $complaint->status,
                'notes' => $request->validated('notes'),
            ]);
        }

        return new ComplaintResource($complaint->load(['complainant', 'company.sector', 'category', 'assignedAdmin', 'attachments', 'updates']));
    }

    public function destroy(Complaint $complaint)
    {
        $complaint->delete();

        return response()->json(['success' => true, 'message' => 'Aduan dihapus']);
    }

    private function nextTicketNumber(): string
    {
        return 'TCK-'.strtoupper(uniqid());
    }
}
