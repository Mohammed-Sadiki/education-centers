<?php
namespace App\Http\Controllers;
use App\Models\Center;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
class InvoiceController extends Controller {
    public function index(Request $request): View {
        Gate::authorize('viewAny', Invoice::class);
        $user = $request->user();
        $query = Invoice::query()->with(['student','enrollment.formation','payments']);
        if ($user->isCenterAdmin()) $query->where('center_id', $user->center_id);
        if ($status = $request->input('status')) $query->where('status', $status);
        $invoices = $query->latest('invoice_date')->paginate(20)->withQueryString();
        $centers = $user->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        return view('invoices.index', compact('invoices','centers'));
    }
    public function create(Request $request): View {
        Gate::authorize('create', Invoice::class);
        $user = $request->user();
        $centerId = $user->isSuperAdmin() ? $request->input('center_id') : $user->center_id;
        $students = Student::when($centerId, fn($q) => $q->where('center_id', $centerId))->where('status','active')->orderBy('last_name')->get();
        $formations = Formation::when($centerId, fn($q) => $q->where('center_id', $centerId))->where('status','active')->orderBy('name')->get();
        $centers = $user->isSuperAdmin() ? Center::orderBy('name')->get() : collect();
        return view('invoices.create', compact('students','formations','centers','centerId'));
    }
    public function store(Request $request): RedirectResponse {
        Gate::authorize('create', Invoice::class);
        $user = $request->user();
        $centerId = $user->isSuperAdmin() ? (int)$request->input('center_id') : $user->center_id;
        $data = $request->validate([
            'student_id'    => ['required','integer','exists:students,id'],
            'enrollment_id' => ['nullable','integer','exists:enrollments,id'],
            'formation_id'  => ['nullable','integer','exists:formations,id'],
            'total_amount'  => ['required','numeric','min:0.01'],
            'currency'      => ['required','string','max:10'],
            'invoice_date'  => ['required','date'],
            'due_date'      => ['nullable','date'],
            'notes'         => ['nullable','string','max:1000'],
        ]);
        $student = Student::findOrFail($data['student_id']);
        if ($student->center_id !== $centerId) abort(403);
        $center = Center::findOrFail($centerId);
        $data['center_id']    = $centerId;
        $data['invoice_number'] = Invoice::generateInvoiceNumber($center);
        $data['status']       = 'sent';
        $data['created_by']   = $user->id;
        $invoice = Invoice::create($data);
        return redirect()->route('invoices.show', $invoice)->with('success', "Facture {$invoice->invoice_number} créée.");
    }
    public function show(Invoice $invoice): View {
        Gate::authorize('view', $invoice);
        $invoice->load(['student.user','enrollment.formation','payments','creator']);
        return view('invoices.show', compact('invoice'));
    }
    public function edit(Invoice $invoice): View {
        Gate::authorize('update', $invoice);
        return view('invoices.edit', compact('invoice'));
    }
    public function update(Request $request, Invoice $invoice): RedirectResponse {
        Gate::authorize('update', $invoice);
        $data = $request->validate([
            'status'   => ['required','in:draft,sent,paid,cancelled,overdue'],
            'due_date' => ['nullable','date'],
            'notes'    => ['nullable','string','max:1000'],
        ]);
        // Auto-pass to paid if total paid >= total
        if ($invoice->total_paid >= $invoice->total_amount) {
            $data['status'] = 'paid';
        }
        $invoice->update($data);
        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture mise à jour.');
    }
    public function destroy(Invoice $invoice): RedirectResponse {
        Gate::authorize('delete', $invoice);
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Facture supprimée.');
    }
}