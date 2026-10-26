<?php

namespace Database\Seeders\Demo;

use App\Models\AccountingEntry;
use App\Models\BankTransaction;
use App\Models\Center;
use App\Models\CenterBudget;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseItem;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSalary;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoFinanceSeeder extends Seeder
{
    use DemoSeederSupport;

    public function run(): void
    {
        foreach (Center::query()->whereIn('code', ['BERLIN', 'MUNICH'])->orderBy('id')->get() as $center) {
            $recorderId = $this->centerAdminId($center);

            $this->seedBudget($center);
            $this->seedInvoicesAndPayments($center, $recorderId);
            $this->seedExpenses($center, $recorderId);
            $this->seedSalaries($center, $recorderId);
            $this->seedOpeningBankTransactions($center, $recorderId);
            $this->seedBankTransactions($center, $recorderId);
            $this->seedAccountingEntries($center, $recorderId);
            $this->recalculateBankBalances($center);
            $this->recalculateAccountingBalances($center);
        }
    }

    private function seedBudget(Center $center): void
    {
        CenterBudget::query()->updateOrCreate(
            [
                'center_id' => $center->id,
                'school_year' => DemoFixtures::SCHOOL_YEAR,
            ],
            [
                'annual_allocated_budget' => $center->code === 'BERLIN' ? 750000.00 : 700000.00,
            ],
        );
    }

    private function seedInvoicesAndPayments(Center $center, int $recorderId): void
    {
        $students = Student::query()
            ->where('center_id', $center->id)
            ->whereNotIn('student_number', ['BER-000001', 'MUN-000001'])
            ->orderBy('student_number')
            ->get();

        $preferred = $center->code === 'BERLIN'
            ? $students->firstWhere('student_number', 'BERLIN-000002')
            : null;

        $orderedStudents = collect([$preferred])
            ->filter()
            ->merge($students->reject(fn (Student $student) => $student->id === $preferred?->id))
            ->values();

        $enrollments = Enrollment::query()
            ->where('center_id', $center->id)
            ->whereIn('student_id', $orderedStudents->pluck('id'))
            ->whereIn('status', [
                Enrollment::STATUS_ACCEPTED,
                Enrollment::STATUS_PENDING,
                Enrollment::STATUS_UNDER_REVIEW,
            ])
            ->with('formation')
            ->orderByDesc('id')
            ->get()
            ->groupBy('student_id');

        $selected = $orderedStudents
            ->filter(fn (Student $student) => $enrollments->get($student->id)?->first()?->formation)
            ->take(40)
            ->values();

        foreach ($selected as $index => $student) {
            /** @var Enrollment $enrollment */
            $enrollment = $enrollments->get($student->id)->first();
            $formation = $enrollment->formation;
            $invoiceNumber = $center->code === 'BERLIN'
                ? ($index === 0 ? 'INV-BERLIN-000001' : sprintf('INV-BERLIN-%06d', 101 + $index))
                : sprintf('INV-MUNICH-%06d', $index + 1);

            [$plan, $invoiceStatus, $dueDate] = $this->invoicePlan($index);

            $invoiceDate = Carbon::parse('2026-08-01')->addDays($index)->toDateString();

            $invoice = Invoice::query()->updateOrCreate(
                ['invoice_number' => $invoiceNumber],
                [
                    'center_id' => $center->id,
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'formation_id' => $formation->id,
                    'total_amount' => $formation->annual_price,
                    'currency' => default_currency(),
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'status' => $invoiceStatus,
                    'notes' => 'Facture de démonstration – '.$formation->name.' ('.DemoFixtures::SCHOOL_YEAR.').',
                    'created_by' => $recorderId,
                ],
            );

            $this->seedInvoicePayments(
                $center,
                $invoice,
                $student,
                $enrollment,
                $formation,
                $index,
                $plan,
                $recorderId,
            );
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function invoicePlan(int $index): array
    {
        return match (true) {
            $index < 8 => ['single', 'paid', Carbon::parse('2026-08-31')->addDays($index)->toDateString()],
            $index < 15 => ['split', 'paid', Carbon::parse('2026-09-05')->addDays($index)->toDateString()],
            $index < 21 => ['partial', 'sent', '2026-12-15'],
            $index < 27 => ['partial', 'overdue', '2026-10-10'],
            $index < 35 => ['unpaid', 'sent', '2026-12-31'],
            default => ['overdue', 'overdue', '2026-09-30'],
        };
    }

    private function seedInvoicePayments(
        Center $center,
        Invoice $invoice,
        Student $student,
        Enrollment $enrollment,
        $formation,
        int $index,
        string $plan,
        int $recorderId,
    ): void {
        $totalCents = (int) round(((float) $invoice->total_amount) * 100);
        $definitions = [];

        if ($plan === 'single') {
            $definitions = [
                ['cents' => $totalCents, 'status' => 'paid', 'days' => 5],
            ];
        } elseif ($plan === 'split') {
            $first = intdiv($totalCents, 2);
            $definitions = [
                ['cents' => $first, 'status' => 'paid', 'days' => 5],
                ['cents' => $totalCents - $first, 'status' => 'paid', 'days' => 35],
            ];
        } elseif ($plan === 'partial') {
            $first = intdiv($totalCents * 40, 100);
            $second = intdiv($totalCents * 25, 100);
            $definitions = [
                ['cents' => $first, 'status' => 'paid', 'days' => 10],
                ['cents' => $second, 'status' => 'paid', 'days' => 45],
            ];
        } elseif ($plan === 'unpaid' && $index < 31) {
            $definitions = [
                ['cents' => intdiv($totalCents * 20, 100), 'status' => 'pending', 'days' => 0],
            ];
        }

        foreach ($definitions as $paymentIndex => $definition) {
            $amount = number_format($definition['cents'] / 100, 2, '.', '');
            $reference = sprintf(
                'PAY-%s-%s-P%d',
                $center->code,
                str_replace('INV-', '', $invoice->invoice_number),
                $paymentIndex + 1,
            );
            $paymentDate = $definition['status'] === 'pending'
                ? '2026-10-20'
                : Carbon::parse($invoice->invoice_date)->addDays($definition['days'])->toDateString();

            Payment::query()->updateOrCreate(
                [
                    'center_id' => $center->id,
                    'reference' => $reference,
                ],
                [
                    'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id,
                    'formation_id' => $formation->id,
                    'invoice_id' => $invoice->id,
                    'school_year' => DemoFixtures::SCHOOL_YEAR,
                    'domain' => $formation->domain,
                    'amount' => $amount,
                    'currency' => default_currency(),
                    'payment_date' => $paymentDate,
                    'method' => ['bank_transfer', 'card', 'cash', 'other'][$paymentIndex % 4],
                    'status' => $definition['status'],
                    'notes' => $definition['status'] === 'paid'
                        ? 'Encaissement de démonstration rattaché à la facture '.$invoice->invoice_number.'.'
                        : 'Paiement en attente de validation – démonstration.',
                    'recorded_by' => $recorderId,
                ],
            );
        }
    }

    private function seedExpenses(Center $center, int $recorderId): void
    {
        $categories = ExpenseCategory::query()->get()->keyBy('code');
        $cc = DemoFixtures::centerCode($center);
        $multiplier = $center->code === 'BERLIN' ? 1.0 : 0.95;

        $definitions = [
            ['loyer', 'Loyer du centre – septembre 2026', 3800, '2026-09-01', 'paid', 'bank_transfer', 'Immobilien '.DemoFixtures::centerLabel($center).' GmbH'],
            ['loyer', 'Loyer du centre – octobre 2026', 3800, '2026-10-01', 'paid', 'bank_transfer', 'Immobilien '.DemoFixtures::centerLabel($center).' GmbH'],
            ['loyer', 'Loyer du centre – novembre 2026', 3800, '2026-11-01', 'pending', 'bank_transfer', 'Immobilien '.DemoFixtures::centerLabel($center).' GmbH'],
            ['electricite', 'Électricité – septembre 2026', 620, '2026-09-10', 'paid', 'bank_transfer', 'Stadtwerke '.DemoFixtures::centerLabel($center)],
            ['electricite', 'Électricité – octobre 2026', 680, '2026-10-10', 'paid', 'bank_transfer', 'Stadtwerke '.DemoFixtures::centerLabel($center)],
            ['internet', 'Abonnement fibre – septembre 2026', 149, '2026-09-05', 'paid', 'credit_card', 'Telekom Business'],
            ['internet', 'Abonnement fibre – octobre 2026', 149, '2026-10-05', 'paid', 'credit_card', 'Telekom Business'],
            ['fournitures', 'Fournitures pédagogiques', 420, '2026-09-18', 'paid', 'check', 'Bürobedarf '.DemoFixtures::centerLabel($center)],
            ['informatique', 'Renouvellement du matériel informatique', 1250, '2026-09-22', 'paid', 'bank_transfer', 'TechPro Solutions'],
            ['entretien', 'Entretien des locaux – septembre', 540, '2026-10-08', 'paid', 'bank_transfer', 'CleanServices GmbH'],
            ['eau', 'Consommation d’eau – septembre 2026', 180, '2026-09-12', 'paid', 'bank_transfer', 'Stadtwerke '.DemoFixtures::centerLabel($center)],
            ['autres', 'Assurance responsabilité civile', 260, '2026-09-15', 'paid', 'bank_transfer', 'Assurances Démo'],
            ['fournitures', 'Réassort papeterie et consommables', 310, '2026-10-20', 'paid', 'cash', 'Bürobedarf '.DemoFixtures::centerLabel($center)],
            ['entretien', 'Maintenance préventive – novembre', 390, '2026-11-15', 'pending', 'bank_transfer', 'CleanServices GmbH'],
            ['autres', 'Équipement des ateliers techniques', 980, '2026-10-18', 'paid', 'bank_transfer', 'Atelier Pro'],
        ];

        $items = [
            7 => [
                ['Papeterie et classeurs', 250.00, 1],
                ['Consommables pédagogiques', 170.00, 1],
            ],
            8 => [
                ['Ordinateurs portables', 950.00, 1],
                ['Accessoires et câbles', 300.00, 1],
            ],
            14 => [
                ['Outillage professionnel', 600.00, 1],
                ['Établi et rangement', 380.00, 1],
            ],
        ];

        foreach ($definitions as $index => [$categoryCode, $title, $baseAmount, $date, $status, $method, $supplier]) {
            $amount = round($baseAmount * $multiplier, 2);
            $reference = sprintf('DEMO-EXP-%s-%03d', $cc, $index + 1);

            $expense = Expense::query()->updateOrCreate(
                ['reference' => $reference],
                [
                    'center_id' => $center->id,
                    'category_id' => $categories->get($categoryCode)?->id,
                    'domain' => in_array($categoryCode, ['informatique'], true)
                        ? 'informatique'
                        : (in_array($categoryCode, ['autres'], true) && $index === 14 ? 'maconnerie' : null),
                    'school_year' => DemoFixtures::SCHOOL_YEAR,
                    'title' => $title,
                    'amount' => $amount,
                    'currency' => default_currency(),
                    'expense_date' => $date,
                    'payment_method' => $method,
                    'supplier' => $supplier.' (fictif)',
                    'reference' => $reference,
                    'description' => 'Dépense fictive de démonstration pour le centre '.DemoFixtures::centerLabel($center).'.',
                    'status' => $status,
                    'recorded_by' => $recorderId,
                ],
            );

            if (isset($items[$index])) {
                $itemScale = $amount / max(0.01, $baseAmount);

                foreach ($items[$index] as [$name, $unitPrice, $quantity]) {
                    $scaledUnit = round($unitPrice * $itemScale, 2);
                    $totalPrice = round($scaledUnit * $quantity, 2);

                    ExpenseItem::query()->updateOrCreate(
                        [
                            'expense_id' => $expense->id,
                            'name' => $name,
                        ],
                        [
                            'unit_price' => $scaledUnit,
                            'quantity' => $quantity,
                            'total_price' => $totalPrice,
                        ],
                    );
                }
            }
        }
    }

    private function seedSalaries(Center $center, int $recorderId): void
    {
        $teachers = Teacher::query()
            ->where('center_id', $center->id)
            ->orderBy('id')
            ->get();

        foreach ($teachers as $index => $teacher) {
            $status = match (true) {
                $index < 6 => 'paid',
                $index < 9 => 'partial',
                default => 'unpaid',
            };
            $expected = (float) $teacher->monthly_salary;
            $paid = match ($status) {
                'paid' => $expected,
                'partial' => round($expected * 0.5, 2),
                default => 0.00,
            };

            TeacherSalary::query()->updateOrCreate(
                [
                    'center_id' => $center->id,
                    'teacher_id' => $teacher->id,
                    'year' => 2026,
                    'month' => 10,
                ],
                [
                    'domain' => $teacher->domain,
                    'school_year' => DemoFixtures::SCHOOL_YEAR,
                    'expected_amount' => $expected,
                    'paid_amount' => $paid,
                    'currency' => default_currency(),
                    'status' => $status,
                    'payment_date' => $status === 'unpaid' ? null : '2026-10-20',
                    'payment_method' => $status === 'unpaid'
                        ? null
                        : ($index % 4 === 0 ? 'Espèces' : 'Virement bancaire'),
                    'reference' => sprintf('SAL-DEMO-%s-%s-2026-10', DemoFixtures::centerCode($center), $teacher->teacher_number),
                    'notes' => 'Salaire fictif d’octobre 2026 pour la démonstration.',
                    'recorded_by' => $recorderId,
                ],
            );
        }
    }

    private function seedOpeningBankTransactions(Center $center, int $recorderId): void
    {
        $cc = DemoFixtures::centerCode($center);
        $openings = [
            [
                'reference' => "OPEN-{$cc}-001",
                'label' => 'Apport initial de trésorerie',
                'origin' => 'Direction financière (fictif)',
                'amount' => $center->code === 'BERLIN' ? 120000.00 : 100000.00,
            ],
            [
                'reference' => "OPEN-{$cc}-002",
                'label' => 'Subvention de démarrage',
                'origin' => 'Collectivité territoriale (fictif)',
                'amount' => $center->code === 'BERLIN' ? 60000.00 : 50000.00,
            ],
        ];

        if ($center->code === 'MUNICH') {
            $legacyRows = BankTransaction::query()
                ->where('center_id', $center->id)
                ->where('label', 'vvv')
                ->orderBy('id')
                ->get();

            foreach ($openings as $index => $opening) {
                $legacy = $legacyRows->get($index);

                if ($legacy) {
                    $legacy->update([
                        'transaction_date' => '2026-08-01',
                        'label' => $opening['label'],
                        'origin' => $opening['origin'],
                        'method' => 'bank_transfer',
                        'type' => 'deposit',
                        'amount' => $opening['amount'],
                        'reference' => $opening['reference'],
                        'recorded_by' => $recorderId,
                    ]);
                }
            }
        }

        foreach ($openings as $opening) {
            BankTransaction::query()->updateOrCreate(
                [
                    'center_id' => $center->id,
                    'reference' => $opening['reference'],
                ],
                [
                    'transaction_date' => '2026-08-01',
                    'label' => $opening['label'],
                    'origin' => $opening['origin'],
                    'method' => 'bank_transfer',
                    'type' => 'deposit',
                    'amount' => $opening['amount'],
                    'running_balance' => 0,
                    'recorded_by' => $recorderId,
                ],
            );
        }
    }

    private function seedBankTransactions(Center $center, int $recorderId): void
    {
        $payments = Payment::query()
            ->where('center_id', $center->id)
            ->where('status', 'paid')
            ->whereIn('method', ['bank_transfer', 'card', 'other'])
            ->get();

        foreach ($payments as $payment) {
            BankTransaction::query()->updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'center_id' => $center->id,
                    'transaction_date' => $payment->payment_date,
                    'label' => 'Encaissement étudiant – '.($payment->reference ?? 'PAY-'.$payment->id),
                    'origin' => 'Paiement étudiant',
                    'method' => match ($payment->method) {
                        'card' => 'credit_card',
                        'other' => 'other',
                        default => 'bank_transfer',
                    },
                    'type' => 'deposit',
                    'amount' => $payment->amount,
                    'running_balance' => 0,
                    'reference' => $payment->reference,
                    'recorded_by' => $payment->recorded_by ?? $recorderId,
                ],
            );
        }

        $expenses = Expense::query()
            ->where('center_id', $center->id)
            ->where('status', 'paid')
            ->whereIn('payment_method', ['bank_transfer', 'check', 'credit_card', 'other'])
            ->get();

        foreach ($expenses as $expense) {
            BankTransaction::query()->updateOrCreate(
                ['expense_id' => $expense->id],
                [
                    'center_id' => $center->id,
                    'transaction_date' => $expense->expense_date,
                    'label' => 'Dépense – '.$expense->title,
                    'origin' => $expense->supplier ?? 'Fournisseur',
                    'method' => $expense->payment_method,
                    'type' => 'withdrawal',
                    'amount' => $expense->amount,
                    'running_balance' => 0,
                    'reference' => $expense->reference,
                    'recorded_by' => $expense->recorded_by ?? $recorderId,
                ],
            );
        }

        $salaries = TeacherSalary::query()
            ->where('center_id', $center->id)
            ->where('paid_amount', '>', 0)
            ->whereNotNull('payment_method')
            ->where('payment_method', 'not like', '%Espèces%')
            ->with('teacher')
            ->get();

        foreach ($salaries as $salary) {
            BankTransaction::query()->updateOrCreate(
                ['teacher_salary_id' => $salary->id],
                [
                    'center_id' => $center->id,
                    'transaction_date' => $salary->payment_date ?? '2026-10-20',
                    'label' => 'Salaire – '.($salary->teacher?->full_name ?? 'Professeur'),
                    'origin' => 'Paie des professeurs',
                    'method' => 'bank_transfer',
                    'type' => 'withdrawal',
                    'amount' => $salary->paid_amount,
                    'running_balance' => 0,
                    'reference' => $salary->reference ?? 'SAL-'.$salary->id,
                    'recorded_by' => $salary->recorded_by ?? $recorderId,
                ],
            );
        }
    }

    private function seedAccountingEntries(Center $center, int $recorderId): void
    {
        $payments = Payment::query()
            ->where('center_id', $center->id)
            ->where('status', 'paid')
            ->with('student')
            ->get();

        foreach ($payments as $payment) {
            $piece = 'ENC-'.($payment->reference ?? 'PAY-'.$payment->id);
            $label = 'Encaissement '.($payment->reference ?? 'PAY-'.$payment->id);
            $thirdParty = $payment->student?->full_name;
            $date = $payment->payment_date;
            $amount = $payment->amount;
            $by = $payment->recorded_by ?? $recorderId;

            $this->upsertAccountingPair($center, $piece, $date, 'Encaissement', $label, $thirdParty, '5141', '706', $amount, $by);
        }

        $expenses = Expense::query()
            ->where('center_id', $center->id)
            ->where('status', 'paid')
            ->get();

        foreach ($expenses as $expense) {
            $piece = 'DEP-'.($expense->reference ?? 'EXP-'.$expense->id);
            $this->upsertAccountingPair(
                $center,
                $piece,
                $expense->expense_date,
                'Dépense',
                $expense->title,
                $expense->supplier,
                '6122',
                '5141',
                $expense->amount,
                $expense->recorded_by ?? $recorderId,
            );
        }

        $salaries = TeacherSalary::query()
            ->where('center_id', $center->id)
            ->where('paid_amount', '>', 0)
            ->with('teacher')
            ->get();

        foreach ($salaries as $salary) {
            $piece = 'SAL-'.($salary->reference ?? 'LEGACY-'.$salary->id);
            $this->upsertAccountingPair(
                $center,
                $piece,
                $salary->payment_date ?? '2026-10-20',
                'Salaire',
                'Salaire '.($salary->teacher?->full_name ?? 'Professeur'),
                $salary->teacher?->full_name,
                '6171',
                '5141',
                $salary->paid_amount,
                $salary->recorded_by ?? $recorderId,
            );
        }

        $openings = BankTransaction::query()
            ->where('center_id', $center->id)
            ->where('reference', 'like', 'OPEN-%')
            ->get();

        foreach ($openings as $opening) {
            $this->upsertAccountingPair(
                $center,
                $opening->reference,
                $opening->transaction_date,
                'Apport',
                $opening->label,
                $opening->origin,
                '5141',
                '101',
                $opening->amount,
                $opening->recorded_by ?? $recorderId,
            );
        }
    }

    private function upsertAccountingPair(
        Center $center,
        string $piece,
        string $date,
        string $type,
        string $label,
        ?string $thirdParty,
        string $debitAccount,
        string $creditAccount,
        $amount,
        int $recorderId,
    ): void {
        $base = [
            'entry_date' => $date,
            'entry_type' => $type,
            'label' => $label,
            'third_party' => $thirdParty,
            'recorded_by' => $recorderId,
        ];

        AccountingEntry::query()->updateOrCreate(
            [
                'center_id' => $center->id,
                'piece_number' => $piece,
                'account_code' => $debitAccount,
            ],
            $base + [
                'debit' => $amount,
                'credit' => 0,
            ],
        );

        AccountingEntry::query()->updateOrCreate(
            [
                'center_id' => $center->id,
                'piece_number' => $piece,
                'account_code' => $creditAccount,
            ],
            $base + [
                'debit' => 0,
                'credit' => $amount,
            ],
        );
    }

    private function recalculateBankBalances(Center $center): void
    {
        $balance = 0;
        $transactions = BankTransaction::query()
            ->where('center_id', $center->id)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        foreach ($transactions as $transaction) {
            $cents = (int) round(((float) $transaction->amount) * 100);
            $balance += $transaction->type === 'deposit' ? $cents : -$cents;
            $transaction->update(['running_balance' => $balance / 100]);
        }
    }

    private function recalculateAccountingBalances(Center $center): void
    {
        $balance = 0;
        $entries = AccountingEntry::query()
            ->where('center_id', $center->id)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $balance += (int) round(((float) $entry->debit) * 100);
            $balance -= (int) round(((float) $entry->credit) * 100);
            $entry->update(['cumulative_balance' => $balance / 100]);
        }
    }
}
