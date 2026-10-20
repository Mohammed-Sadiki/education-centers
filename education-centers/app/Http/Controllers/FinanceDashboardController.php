<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreStudentPaymentRequest;
use App\Http\Requests\Finance\StoreTeacherSalaryPaymentRequest;
use App\Http\Requests\Finance\UpdateFinancePricesRequest;
use App\Models\Center;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use App\Services\FinanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceDashboardController extends Controller
{
    public function __construct(
        protected FinanceService $financeService
    ) {}

    /**
     * Dashboard financier GLOBAL du Super Admin.
     */
    public function index(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $month      = (int) $request->query('month', (int) now()->format('n'));
        if ($month < 1 || $month > 12) {
            $month = 10;
        }
        $year       = (int) $request->query('year', (int) now()->format('Y'));
        if ($year < 2020 || $year > 2035) {
            $year = 2026;
        }
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;

        $kpis        = $this->financeService->getOverviewKpis($domain, $schoolYear, $month, $year, $centerId);
        $studentRows = $this->financeService->getStudentFinancialRows($domain, $schoolYear, $centerId);
        $teacherRows = $this->financeService->getTeacherSalaryRows($domain, $month, $year, $schoolYear, $centerId);

        $domains     = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers     = Center::orderBy('name')->get();

        // Récupérer les étudiants et formations pour le modal d'ajout rapide de paiement
        $studentsList = Student::with('center')->where('status', 'active')->orderBy('last_name')->get();
        $formationsList = Formation::with('center')->where('status', 'active')->orderBy('name')->get();
        $teachersList = Teacher::with('center')->where('status', 'active')->orderBy('last_name')->get();

        return view('super-admin.finance.dashboard', compact(
            'kpis',
            'studentRows',
            'teacherRows',
            'domain',
            'schoolYear',
            'month',
            'year',
            'centerId',
            'domains',
            'schoolYears',
            'centers',
            'studentsList',
            'formationsList',
            'teachersList'
        ));
    }

    /**
     * Vue dédiée : Paiements étudiants
     */
    public function students(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $status     = $request->query('status', 'all');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;

        $studentRows = $this->financeService->getStudentFinancialRows($domain, $schoolYear, $centerId);

        if ($status !== 'all') {
            $studentRows = $studentRows->where('status', $status);
        }

        $domains     = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers     = Center::orderBy('name')->get();

        $studentsList = Student::with('center')->where('status', 'active')->orderBy('last_name')->get();
        $formationsList = Formation::with('center')->where('status', 'active')->orderBy('name')->get();

        return view('super-admin.finance.students', compact(
            'studentRows',
            'domain',
            'schoolYear',
            'status',
            'centerId',
            'domains',
            'schoolYears',
            'centers',
            'studentsList',
            'formationsList'
        ));
    }

    /**
     * Vue dédiée : Salaires professeurs
     */
    public function teachers(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $month      = (int) $request->query('month', 10);
        $year       = (int) $request->query('year', 2026);
        $status     = $request->query('status', 'all');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;

        $teacherRows = $this->financeService->getTeacherSalaryRows($domain, $month, $year, $schoolYear, $centerId);

        if ($status !== 'all') {
            $teacherRows = $teacherRows->where('status', $status);
        }

        $domains     = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers     = Center::orderBy('name')->get();
        $teachersList = Teacher::with('center')->where('status', 'active')->orderBy('last_name')->get();

        return view('super-admin.finance.teachers', compact(
            'teacherRows',
            'domain',
            'schoolYear',
            'month',
            'year',
            'status',
            'centerId',
            'domains',
            'schoolYears',
            'centers',
            'teachersList'
        ));
    }

    /**
     * Vue dédiée : Historique des paiements
     */
    public function history(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', 'all');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;

        $paymentsQuery = Payment::with(['student.user', 'formation.center', 'center', 'recorder'])
            ->latest('payment_date');

        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }

        if ($schoolYear !== 'all') {
            $paymentsQuery->where('school_year', $schoolYear);
        }

        if ($domain && $domain !== 'all') {
            $paymentsQuery->where(function ($q) use ($domain) {
                $q->where('domain', $domain)
                  ->orWhereHas('formation', fn($f) => $f->where('domain', $domain));
            });
        }

        $payments = $paymentsQuery->paginate(25)->withQueryString();
        $domains     = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers     = Center::orderBy('name')->get();

        return view('super-admin.finance.history', compact(
            'payments',
            'domain',
            'schoolYear',
            'centerId',
            'domains',
            'schoolYears',
            'centers'
        ));
    }

    /**
     * Page de configuration des prix annuels et des salaires mensuels.
     */
    public function configuration(): View
    {
        $formations = Formation::with('center')->orderBy('name')->get();
        $teachers   = Teacher::with(['user', 'center'])->orderBy('last_name')->get();
        $domains    = FinanceService::getDomains();

        return view('super-admin.finance.configuration', compact('formations', 'teachers', 'domains'));
    }

    /**
     * Enregistrer les modifications de prix et salaires configurables.
     */
    public function updateConfiguration(UpdateFinancePricesRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['formations'])) {
            foreach ($data['formations'] as $fData) {
                Formation::where('id', $fData['id'])->update([
                    'annual_price' => $fData['annual_price'],
                    'domain'       => $fData['domain'] ?? null,
                ]);
            }
        }

        if (! empty($data['teachers'])) {
            foreach ($data['teachers'] as $tData) {
                Teacher::where('id', $tData['id'])->update([
                    'monthly_salary' => $tData['monthly_salary'],
                    'domain'         => $tData['domain'] ?? null,
                ]);
            }
        }

        return redirect()->route('super-admin.finance.configuration')
            ->with('success', 'Les prix annuels et salaires mensuels ont été mis à jour avec succès.');
    }

    /**
     * Enregistrer un paiement étudiant depuis le module financier.
     */
    public function storeStudentPayment(StoreStudentPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $student = Student::with('center')->findOrFail($data['student_id']);
        $formation = ! empty($data['formation_id']) ? Formation::find($data['formation_id']) : null;

        $reference = $data['reference'] ?? Payment::generateReference($student->center);

        Payment::create([
            'center_id'     => $student->center_id,
            'student_id'    => $student->id,
            'formation_id'  => $formation?->id,
            'enrollment_id' => $data['enrollment_id'] ?? null,
            'school_year'   => $data['school_year'],
            'domain'        => $formation?->domain ?? null,
            'amount'        => $data['amount'],
            'currency'      => $data['currency'],
            'payment_date'  => $data['payment_date'],
            'method'        => $data['method'],
            'reference'     => $reference,
            'status'        => 'paid',
            'notes'         => $data['notes'] ?? null,
            'recorded_by'   => $request->user()->id,
        ]);

        return back()->with('success', "Paiement de {$data['amount']} {$data['currency']} enregistré avec succès pour {$student->full_name}.");
    }

    /**
     * Enregistrer le paiement d'un salaire professeur.
     */
    public function storeTeacherSalaryPayment(StoreTeacherSalaryPaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $teacher = Teacher::with('center')->findOrFail($data['teacher_id']);

        $expected = ! empty($data['expected_amount']) && (float) $data['expected_amount'] > 0
            ? (float) $data['expected_amount']
            : FinanceService::getTeacherMonthlySalary($teacher);

        $paid = (float) $data['paid_amount'];

        if ($paid >= $expected && $expected > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } else {
            $status = 'unpaid';
        }

        TeacherSalary::updateOrCreate(
            [
                'center_id'  => $teacher->center_id,
                'teacher_id' => $teacher->id,
                'month'      => $data['month'],
                'year'       => $data['year'],
            ],
            [
                'domain'          => $teacher->domain,
                'school_year'     => $data['school_year'],
                'expected_amount' => $expected,
                'paid_amount'     => $paid,
                'currency'        => $data['currency'],
                'status'          => $status,
                'payment_date'    => $data['payment_date'] ?? now()->toDateString(),
                'payment_method'  => $data['payment_method'] ?? 'Virement bancaire',
                'notes'           => $data['notes'] ?? null,
                'recorded_by'     => $request->user()->id,
            ]
        );

        return back()->with('success', "Paiement de salaire enregistré pour {$teacher->full_name}.");
    }

    /**
     * Page Rapports financiers (placeholder propre "Bientôt disponible").
     */
    public function reports(): View
    {
        return view('super-admin.finance.reports');
    }
}
