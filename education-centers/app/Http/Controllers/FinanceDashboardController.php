<?php

namespace App\Http\Controllers;

use App\Http\Requests\Finance\StoreStudentPaymentRequest;
use App\Http\Requests\Finance\StoreTeacherSalaryPaymentRequest;
use App\Http\Requests\Finance\UpdateFinancePricesRequest;
use App\Models\Center;
use App\Models\Enrollment;
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

        // Données JSON pour pré-remplissage dynamique dans le modal de paiement
        $studentFinancialMap = $studentRows->keyBy('student.id')->map(function ($row) {
            return [
                'student_id'       => $row->student->id,
                'student_name'     => $row->student->full_name,
                'student_number'   => $row->student->student_number,
                'center_name'      => $row->center?->name ?? 'N/A',
                'formation_id'     => $row->formation->id,
                'formation_name'   => $row->formation->name,
                'annual_price'     => $row->annual_price,
                'paid_amount'      => $row->paid_amount,
                'remaining_amount' => $row->remaining_amount,
                'status'           => $row->status,
            ];
        });

        $monthsNames = FinanceService::MONTHS_NAMES;

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
            'teachersList',
            'studentFinancialMap',
            'monthsNames'
        ));
    }

    /**
     * Vue dédiée : Paiements étudiants
     */
    public function students(Request $request): View
    {
        $domain      = $request->query('domain', 'all');
        $schoolYear  = $request->query('school_year', '2026/2027');
        $status      = $request->query('status', 'all');
        $centerId    = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $formationId = $request->query('formation_id') ? (int) $request->query('formation_id') : null;
        $search      = trim((string) $request->query('search', ''));
        $viewMode    = $request->query('view', 'students'); // 'students' | 'monthly' | 'unpaid'

        $allStudentRows = $this->financeService->getStudentFinancialRows($domain, $schoolYear, $centerId);

        // Calcul des métriques globales de la sélection (avant filtrage textuel ou de statut)
        $stats = [
            'total_expected'  => (float) $allStudentRows->sum('annual_price'),
            'total_paid'      => (float) $allStudentRows->sum('paid_amount'),
            'total_remaining' => (float) $allStudentRows->sum('remaining_amount'),
            'count_paid'      => $allStudentRows->where('status', 'paid')->count(),
            'count_partial'   => $allStudentRows->where('status', 'partial')->count(),
            'count_unpaid'    => $allStudentRows->where('status', 'unpaid')->count(),
            'total_students'  => $allStudentRows->count(),
            'recovery_rate'   => $allStudentRows->sum('annual_price') > 0
                ? round(($allStudentRows->sum('paid_amount') / $allStudentRows->sum('annual_price')) * 100, 1)
                : 0.0,
        ];

        $studentRows = $allStudentRows;

        // Filtre par statut si renseigné
        if ($status !== 'all') {
            $studentRows = $studentRows->where('status', $status);
        }

        // Filtre par formation
        if ($formationId) {
            $studentRows = $studentRows->filter(fn($row) => (int) $row->formation->id === $formationId);
        }

        // Filtre par recherche texte (nom, prénom, matricule ou référence de paiement)
        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $studentRows = $studentRows->filter(function ($row) use ($searchLower) {
                $nameMatches = str_contains(mb_strtolower($row->student->full_name), $searchLower)
                    || str_contains(mb_strtolower($row->student->student_number ?? ''), $searchLower);

                $refMatches = $row->payments->contains(function ($payment) use ($searchLower) {
                    return str_contains(mb_strtolower($payment->reference ?? ''), $searchLower);
                });

                return $nameMatches || $refMatches;
            });
        }

        // Mode Recouvrement : étudiants ayant un solde restant > 0
        if ($viewMode === 'unpaid') {
            $studentRows = $studentRows->filter(fn($row) => $row->remaining_amount > 0)
                ->sortByDesc('remaining_amount');
        }

        // Récupérer les versements groupés par mois (pour la vue accordéon)
        $monthlyGroups = $this->financeService->getMonthlyPaymentGroups($domain, $schoolYear, $centerId);

        $domains        = FinanceService::getDomains();
        $schoolYears    = FinanceService::SCHOOL_YEARS;
        $centers        = Center::orderBy('name')->get();
        $studentsList   = Student::with('center')->where('status', 'active')->orderBy('last_name')->get();
        $formationsList = Formation::with('center')->where('status', 'active')->orderBy('name')->get();

        // Données JSON pour le modal
        $studentFinancialMap = $allStudentRows->keyBy('student.id')->map(function ($row) {
            return [
                'student_id'       => $row->student->id,
                'student_name'     => $row->student->full_name,
                'student_number'   => $row->student->student_number,
                'center_name'      => $row->center?->name ?? 'N/A',
                'formation_id'     => $row->formation->id,
                'formation_name'   => $row->formation->name,
                'annual_price'     => $row->annual_price,
                'paid_amount'      => $row->paid_amount,
                'remaining_amount' => $row->remaining_amount,
                'status'           => $row->status,
            ];
        });

        $formations = $formationsList;

        return view('super-admin.finance.students', compact(
            'studentRows',
            'monthlyGroups',
            'stats',
            'domain',
            'schoolYear',
            'status',
            'centerId',
            'formationId',
            'search',
            'viewMode',
            'domains',
            'schoolYears',
            'centers',
            'studentsList',
            'formationsList',
            'formations',
            'studentFinancialMap'
        ));
    }

    /**
     * Vue dédiée : Salaires professeurs
     */
    public function teachers(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $month      = (int) $request->query('month', (int) now()->format('n'));
        if ($month < 1 || $month > 12) {
            $month = 10;
        }
        $year       = (int) $request->query('year', 2026);
        $status     = $request->query('status', 'all');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $search     = trim((string) $request->query('search', ''));

        $allTeacherRows = $this->financeService->getTeacherSalaryRows($domain, $month, $year, $schoolYear, $centerId);

        $teacherRows = $allTeacherRows;

        if ($status !== 'all') {
            $teacherRows = $teacherRows->where('status', $status);
        }

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $teacherRows = $teacherRows->filter(function ($row) use ($searchLower) {
                return str_contains(mb_strtolower($row->teacher->full_name), $searchLower)
                    || str_contains(mb_strtolower($row->teacher->teacher_number ?? ''), $searchLower);
            });
        }

        // Statistiques globales du mois
        $stats = [
            'total_attendus' => (float) $allTeacherRows->sum('monthly_salary'),
            'total_payes'    => (float) $allTeacherRows->sum('paid_amount'),
            'total_restant'  => (float) $allTeacherRows->sum('remaining_amount'),
            'count_teachers' => $allTeacherRows->count(),
            'count_paid'     => $allTeacherRows->where('status', 'paid')->count(),
            'count_partial'  => $allTeacherRows->where('status', 'partial')->count(),
            'count_unpaid'   => $allTeacherRows->where('status', 'unpaid')->count(),
            'rate_paid'      => $allTeacherRows->sum('monthly_salary') > 0
                ? round(($allTeacherRows->sum('paid_amount') / $allTeacherRows->sum('monthly_salary')) * 100, 1)
                : 0.0,
        ];

        $domains      = FinanceService::getDomains();
        $schoolYears  = FinanceService::SCHOOL_YEARS;
        $centers      = Center::orderBy('name')->get();
        $teachersList = Teacher::with('center')->where('status', 'active')->orderBy('last_name')->get();
        $monthsNames  = FinanceService::MONTHS_NAMES;

        return view('super-admin.finance.teachers', compact(
            'teacherRows',
            'stats',
            'domain',
            'schoolYear',
            'month',
            'year',
            'status',
            'centerId',
            'search',
            'domains',
            'schoolYears',
            'centers',
            'teachersList',
            'monthsNames'
        ));
    }

    /**
     * Vue dédiée : Historique des paiements
     */
    public function history(Request $request): View
    {
        $domain        = $request->query('domain', 'all');
        $schoolYear    = $request->query('school_year', 'all');
        $paymentMethod = $request->query('payment_method', $request->query('method', 'all'));
        $method        = $paymentMethod;
        $centerId      = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $search        = trim((string) $request->query('search', ''));

        $paymentsQuery = Payment::with(['student.user', 'formation.center', 'center', 'recorder'])
            ->latest('payment_date');

        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
        }

        if ($schoolYear !== 'all') {
            $paymentsQuery->where('school_year', $schoolYear);
        }

        if ($method !== 'all') {
            $paymentsQuery->where('method', $method);
        }

        if ($domain && $domain !== 'all') {
            $paymentsQuery->where(function ($q) use ($domain) {
                $q->where('domain', $domain)
                  ->orWhereHas('formation', fn($f) => $f->where('domain', $domain));
            });
        }

        if ($search !== '') {
            $searchLower = "%{$search}%";
            $paymentsQuery->where(function ($q) use ($searchLower) {
                $q->where('reference', 'LIKE', $searchLower)
                  ->orWhere('notes', 'LIKE', $searchLower)
                  ->orWhereHas('student', function ($sq) use ($searchLower) {
                      $sq->where('first_name', 'LIKE', $searchLower)
                        ->orWhere('last_name', 'LIKE', $searchLower)
                        ->orWhere('student_number', 'LIKE', $searchLower);
                  });
            });
        }

        // Totaux globaux des paiements filtrés
        $stats = [
            'total_count'    => (clone $paymentsQuery)->count(),
            'total_amount'   => (float) (clone $paymentsQuery)->where('status', 'paid')->sum('amount'),
            'cash_total'     => (float) (clone $paymentsQuery)->where('status', 'paid')->where('method', 'cash')->sum('amount'),
            'transfer_total' => (float) (clone $paymentsQuery)->where('status', 'paid')->where('method', 'bank_transfer')->sum('amount'),
            'card_total'     => (float) (clone $paymentsQuery)->where('status', 'paid')->where('method', 'card')->sum('amount'),
        ];

        $payments    = $paymentsQuery->paginate(25)->withQueryString();
        $domains     = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers     = Center::orderBy('name')->get();

        return view('super-admin.finance.history', compact(
            'payments',
            'stats',
            'domain',
            'schoolYear',
            'method',
            'paymentMethod',
            'centerId',
            'search',
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

        // Vérification de sécurité métier : vérifier que le montant ne dépasse pas le solde restant si l'inscription existe
        $enrollment = ! empty($data['enrollment_id'])
            ? Enrollment::find($data['enrollment_id'])
            : Enrollment::where('student_id', $student->id)
                ->where('school_year', $data['school_year'])
                ->first();

        $targetFormation = $formation ?? $enrollment?->formation;
        if ($targetFormation) {
            $expected = FinanceService::getFormationAnnualPrice($targetFormation);
            $alreadyPaid = (float) Payment::where('student_id', $student->id)
                ->where('school_year', $data['school_year'])
                ->where('status', 'paid')
                ->sum('amount');

            $remaining = max(0.00, $expected - $alreadyPaid);
            if ((float) $data['amount'] > $remaining + 0.05 && $remaining > 0) {
                return back()->withInput()->withErrors([
                    'amount' => "Le montant saisi (" . format_price($data['amount'], 'MAD') . ") dépasse le solde restant dû de l'étudiant (" . format_price($remaining, 'MAD') . ").",
                ]);
            }
        }

        $reference = ! empty($data['reference']) ? $data['reference'] : Payment::generateReference($student->center);

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
