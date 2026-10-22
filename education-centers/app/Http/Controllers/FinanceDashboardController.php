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
     * Page Rapports financiers.
     */
    public function reports(): View
    {
        return view('super-admin.finance.reports');
    }

    /**
     * Page Statistiques (Capture 3 Cad Syndic).
     */
    public function statistics(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $month      = (int) $request->query('month', (int) now()->format('n'));
        $year       = (int) $request->query('year', (int) now()->format('Y'));
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;

        $kpis = $this->financeService->getOverviewKpis($domain, $schoolYear, $month, $year, $centerId);
        $domains = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers = Center::orderBy('name')->get();

        // Récupérer les données réelles de dépenses pour le comparatif
        $expensesQuery = \App\Models\Expense::where('status', 'paid');
        if ($centerId) {
            $expensesQuery->where('center_id', $centerId);
        }
        $expensesTotal = (float) $expensesQuery->sum('amount');

        return view('super-admin.finance.statistics', compact(
            'kpis',
            'domain',
            'schoolYear',
            'month',
            'year',
            'centerId',
            'domains',
            'schoolYears',
            'centers',
            'expensesTotal'
        ));
    }

    /**
     * Page Dépenses (Capture 1 Cad Syndic).
     */
    public function expenses(Request $request): View
    {
        $schoolYear = $request->query('school_year', '2026/2027');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $categoryId = $request->query('category_id') ? (int) $request->query('category_id') : null;
        $search     = $request->query('search');

        $query = \App\Models\Expense::with(['category', 'center', 'recorder', 'items'])
            ->where('school_year', $schoolYear);

        if ($centerId) {
            $query->where('center_id', $centerId);
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenses = $query->orderByDesc('expense_date')->get();

        // Budget annuel
        $budgetQuery = \App\Models\CenterBudget::where('school_year', $schoolYear);
        if ($centerId) {
            $budgetQuery->where('center_id', $centerId);
        }
        $annualBudget = (float) $budgetQuery->sum('annual_allocated_budget');
        if ($annualBudget <= 0) {
            $annualBudget = 500000.00; // Seuil de référence par défaut
        }

        // Seules les dépenses non annulées sont engagées sur le budget
        $totalExpenses = (float) $expenses->where('status', '!=', 'cancelled')->sum('amount');
        $remainingBudget = max(0, $annualBudget - $totalExpenses);
        $consumptionRate = $annualBudget > 0 ? round(($totalExpenses / $annualBudget) * 100, 1) : 0;

        // Groupement par mois pour les accordéons Cad Syndic
        $monthsNames = FinanceService::MONTHS_NAMES;
        $groupedExpenses = $expenses->groupBy(function ($exp) {
            return $exp->expense_date ? $exp->expense_date->format('Y-m') : 'Sans date';
        })->map(function ($items, $key) use ($monthsNames) {
            $date = \Carbon\Carbon::parse($key . '-01');
            $mNum = (int) $date->format('n');
            $mLabel = ($monthsNames[$mNum] ?? '') . ' ' . $date->format('Y');

            $cashTotal = (float) $items->where('payment_method', 'cash')->sum('amount');
            $checkTotal = (float) $items->where('payment_method', 'check')->sum('amount');
            $transferTotal = (float) $items->where('payment_method', 'bank_transfer')->sum('amount');
            $monthTotal = (float) $items->sum('amount');

            return (object) [
                'period_key'     => $key,
                'period_label'   => $mLabel,
                'badge'          => strtoupper(substr($monthsNames[$mNum] ?? 'MOIS', 0, 3)),
                'expenses_count' => $items->count(),
                'total_amount'   => $monthTotal,
                'cash_total'     => $cashTotal,
                'check_total'    => $checkTotal,
                'transfer_total' => $transferTotal,
                'items'          => $items,
            ];
        });

        $categories = \App\Models\ExpenseCategory::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $schoolYears = FinanceService::SCHOOL_YEARS;

        return view('super-admin.finance.expenses', compact(
            'expenses',
            'groupedExpenses',
            'annualBudget',
            'totalExpenses',
            'remainingBudget',
            'consumptionRate',
            'categories',
            'centers',
            'schoolYears',
            'schoolYear',
            'centerId',
            'categoryId',
            'search'
        ));
    }

    /**
     * Enregistrer une nouvelle dépense avec ses articles et justificatif.
     */
    public function storeExpense(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'center_id'        => 'required|exists:centers,id',
            'category_id'      => 'nullable|exists:expense_categories,id',
            'title'            => 'required|string|max:255',
            'expense_date'     => 'required|date',
            'payment_method'   => 'required|in:cash,check,bank_transfer,credit_card,other',
            'supplier'         => 'nullable|string|max:150',
            'reference'        => 'nullable|string|max:100',
            'description'      => 'nullable|string',
            'school_year'      => 'nullable|string|max:20',
            'receipt'          => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'items'            => 'nullable|array',
            'items.*.name'     => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
            'items.*.unit_price' => 'required_with:items|numeric|min:0',
            'amount'           => 'nullable|numeric|min:0.01',
        ]);

        // Calcul du montant total à partir des articles s'ils sont fournis
        $itemsData = $request->input('items', []);
        $totalCalculated = 0.00;

        if (! empty($itemsData)) {
            foreach ($itemsData as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $totalCalculated += ($qty * $unitPrice);
            }
        }

        // Si aucun article n'a été spécifié, utiliser le montant direct
        $finalAmount = $totalCalculated > 0 ? $totalCalculated : (float) ($request->input('amount') ?? 0);
        if ($finalAmount <= 0) {
            return back()->withErrors(['amount' => 'Le montant total de la dépense doit être supérieur à zéro.'])->withInput();
        }

        // Traitement du justificatif
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('expenses/receipts', 'public');
        }

        $expense = \App\Models\Expense::create([
            'center_id'      => $validated['center_id'],
            'category_id'    => $validated['category_id'] ?? null,
            'title'          => $validated['title'],
            'amount'         => $finalAmount,
            'currency'       => 'MAD',
            'expense_date'   => $validated['expense_date'],
            'payment_method' => $validated['payment_method'],
            'supplier'       => $validated['supplier'] ?? null,
            'reference'      => $validated['reference'] ?? null,
            'receipt_path'   => $receiptPath,
            'description'    => $validated['description'] ?? null,
            'school_year'    => $validated['school_year'] ?? '2026/2027',
            'status'         => 'paid',
            'recorded_by'    => $request->user()->id,
        ]);

        // Enregistrer les articles achetés
        if (! empty($itemsData)) {
            foreach ($itemsData as $it) {
                $qty = (int) ($it['quantity'] ?? 1);
                $unitPrice = (float) ($it['unit_price'] ?? 0);
                \App\Models\ExpenseItem::create([
                    'expense_id'  => $expense->id,
                    'name'        => $it['name'],
                    'unit_price'  => $unitPrice,
                    'quantity'    => $qty,
                    'total_price' => ($qty * $unitPrice),
                ]);
            }
        }

        // Tracer dans le journal bancaire pour les paiements bancaires, cartes ou chèques
        if (in_array($validated['payment_method'], ['bank_transfer', 'check', 'credit_card'])) {
            $lastBalance = (float) \App\Models\BankTransaction::where('center_id', $validated['center_id'])->latest('id')->value('running_balance') ?? 0;
            \App\Models\BankTransaction::create([
                'center_id'        => $validated['center_id'],
                'transaction_date' => $validated['expense_date'],
                'label'            => 'Dépense : ' . $validated['title'] . ($expense->supplier ? " ({$expense->supplier})" : ''),
                'origin'           => 'Dépense d\'exploitation',
                'method'           => $validated['payment_method'],
                'type'             => 'withdrawal',
                'amount'           => $finalAmount,
                'running_balance'  => $lastBalance - $finalAmount,
                'reference'        => $validated['reference'] ?? ('DEP-' . str_pad($expense->id, 5, '0', STR_PAD_LEFT)),
                'receipt_path'     => $receiptPath,
                'expense_id'       => $expense->id,
                'recorded_by'      => $request->user()->id,
            ]);
        }

        // Écriture comptable (Débit Compte de charge 6122, Crédit Banque 5141 ou Caisse 5161)
        \App\Models\AccountingEntry::create([
            'center_id'          => $validated['center_id'],
            'piece_number'       => $validated['reference'] ?? ('DEP-' . str_pad($expense->id, 5, '0', STR_PAD_LEFT)),
            'entry_date'         => $validated['expense_date'],
            'entry_type'         => 'Dépense',
            'label'              => $validated['title'],
            'third_party'        => $validated['supplier'] ?? 'Fournisseur divers',
            'account_code'       => '6122',
            'debit'              => $finalAmount,
            'credit'             => 0.00,
            'cumulative_balance' => -$finalAmount,
            'recorded_by'        => $request->user()->id,
        ]);

        return back()->with('success', "Achat de {$finalAmount} MAD enregistré avec succès.");
    }

    /**
     * Annuler une dépense enregistrée.
     */
    public function cancelExpense(\App\Models\Expense $expense): RedirectResponse
    {
        if ($expense->status === 'cancelled') {
            return back()->with('error', 'Cette dépense est déjà annulée.');
        }

        $expense->update(['status' => 'cancelled']);

        // Si une écriture bancaire était associée, créer une contrepassation de crédit
        $linkedBank = \App\Models\BankTransaction::where('expense_id', $expense->id)->first();
        if ($linkedBank) {
            $lastBalance = (float) \App\Models\BankTransaction::where('center_id', $expense->center_id)->latest('id')->value('running_balance') ?? 0;
            \App\Models\BankTransaction::create([
                'center_id'        => $expense->center_id,
                'transaction_date' => now()->toDateString(),
                'label'            => 'Contrepassation annulation : ' . $expense->title,
                'origin'           => 'Annulation dépense',
                'method'           => $linkedBank->method,
                'type'             => 'deposit',
                'amount'           => $expense->amount,
                'running_balance'  => $lastBalance + (float) $expense->amount,
                'reference'        => 'ANNUL-' . $expense->id,
                'recorded_by'      => auth()->id(),
            ]);
        }

        return back()->with('success', 'Dépense annulée avec succès et budget réajusté.');
    }

    /**
     * Supprimer une dépense.
     */
    public function destroyExpense(\App\Models\Expense $expense): RedirectResponse
    {
        // Supprimer les transactions bancaires et comptables liées
        \App\Models\BankTransaction::where('expense_id', $expense->id)->delete();
        $expense->delete();

        return back()->with('success', 'Dépense supprimée.');
    }

    /**
     * Télécharger ou prévisualiser le justificatif d'une dépense.
     */
    public function downloadExpenseReceipt(\App\Models\Expense $expense)
    {
        if (! $expense->receipt_path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($expense->receipt_path)) {
            return back()->with('error', 'Aucun justificatif trouvé pour cette dépense.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->response($expense->receipt_path);
    }

    /**
     * Page Recouvrement (Capture 6 Cad Syndic).
     */
    public function recovery(Request $request): View
    {
        $domain     = $request->query('domain', 'all');
        $schoolYear = $request->query('school_year', '2026/2027');
        $centerId   = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $search     = $request->query('search');

        $rows = $this->financeService->getStudentFinancialRows($domain, $schoolYear, $centerId);

        // Filtrer uniquement les dossiers avec un reste à charge
        $debtors = $rows->where('remaining_amount', '>', 0);

        if ($search) {
            $debtors = $debtors->filter(function ($row) use ($search) {
                return str_contains(strtolower($row->student->full_name), strtolower($search))
                    || str_contains(strtolower($row->student->student_number ?? ''), strtolower($search))
                    || str_contains(strtolower($row->formation->name), strtolower($search));
            });
        }

        $debtors = $debtors->sortByDesc('remaining_amount');

        $totalDebt = (float) $debtors->sum('remaining_amount');
        $debtorsCount = $debtors->count();
        $unpaidCount = $debtors->where('status', 'unpaid')->count();

        $domains = FinanceService::getDomains();
        $schoolYears = FinanceService::SCHOOL_YEARS;
        $centers = Center::orderBy('name')->get();

        return view('super-admin.finance.recovery', compact(
            'debtors',
            'totalDebt',
            'debtorsCount',
            'unpaidCount',
            'domain',
            'schoolYear',
            'centerId',
            'domains',
            'schoolYears',
            'centers',
            'search'
        ));
    }

    /**
     * Page Compte bancaire (Capture 7 Cad Syndic).
     */
    public function bank(Request $request): View
    {
        $centerId = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $type     = $request->query('type', 'all');
        $search   = $request->query('search');

        $query = \App\Models\BankTransaction::with(['center', 'recorder']);

        if ($centerId) {
            $query->where('center_id', $centerId);
        }
        if ($type !== 'all') {
            $query->where('type', $type);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderByDesc('transaction_date')->orderByDesc('id')->get();

        $totalDeposits = (float) \App\Models\BankTransaction::when($centerId, fn($q) => $q->where('center_id', $centerId))->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = (float) \App\Models\BankTransaction::when($centerId, fn($q) => $q->where('center_id', $centerId))->where('type', 'withdrawal')->sum('amount');
        $bankBalance = $totalDeposits - $totalWithdrawals;

        $centers = Center::orderBy('name')->get();

        return view('super-admin.finance.bank', compact(
            'transactions',
            'totalDeposits',
            'totalWithdrawals',
            'bankBalance',
            'centerId',
            'type',
            'search',
            'centers'
        ));
    }

    /**
     * Enregistrer une écriture bancaire manuelle.
     */
    public function storeBankTransaction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'center_id'        => 'required|exists:centers,id',
            'transaction_date' => 'required|date',
            'label'            => 'required|string|max:255',
            'origin'           => 'required|string|max:100',
            'method'           => 'required|in:bank_transfer,check,cash,credit_card,other',
            'type'             => 'required|in:deposit,withdrawal',
            'amount'           => 'required|numeric|min:0.01',
            'reference'        => 'nullable|string|max:100',
        ]);

        $lastBalance = (float) \App\Models\BankTransaction::where('center_id', $validated['center_id'])->latest('id')->value('running_balance') ?? 0;
        $amount = (float) $validated['amount'];
        $newBalance = $validated['type'] === 'deposit' ? ($lastBalance + $amount) : ($lastBalance - $amount);

        $validated['running_balance'] = $newBalance;
        $validated['recorded_by']     = $request->user()->id;

        \App\Models\BankTransaction::create($validated);

        return back()->with('success', 'Écriture bancaire enregistrée.');
    }

    /**
     * Page Comptabilité : Journal, CPC, Bilan, Grand Livre (Capture 8 Cad Syndic).
     */
    public function accounting(Request $request): View
    {
        $tab      = $request->query('tab', 'journal'); // journal, cpc, bilan, grand_livre
        $centerId = $request->query('center_id') ? (int) $request->query('center_id') : null;
        $year     = (int) $request->query('year', (int) now()->format('Y'));

        // Données consolidées réelles
        $paymentsQuery = Payment::where('status', 'paid');
        $expensesQuery = \App\Models\Expense::where('status', 'paid');
        $salariesQuery = TeacherSalary::where('status', 'paid');

        if ($centerId) {
            $paymentsQuery->where('center_id', $centerId);
            $expensesQuery->where('center_id', $centerId);
            $salariesQuery->where('center_id', $centerId);
        }

        $totalProducts = (float) $paymentsQuery->sum('amount');
        $totalChargesExpenses = (float) $expensesQuery->sum('amount');
        $totalChargesSalaries = (float) $salariesQuery->sum('paid_amount');
        $totalCharges = $totalChargesExpenses + $totalChargesSalaries;
        $netResult = $totalProducts - $totalCharges;

        // Journal des écritures réelles
        $entries = \App\Models\AccountingEntry::when($centerId, fn($q) => $q->where('center_id', $centerId))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get();

        $centers = Center::orderBy('name')->get();

        return view('super-admin.finance.accounting', compact(
            'tab',
            'centerId',
            'year',
            'centers',
            'totalProducts',
            'totalCharges',
            'totalChargesExpenses',
            'totalChargesSalaries',
            'netResult',
            'entries'
        ));
    }
}
