<?php

namespace App\Support;

use App\Models\DayBookEntry;
use App\Models\PurchaseFile;

final class PurchaseFileSheetGrid
{
    /**
     * @param  array<string, mixed>  $data  Output from buildPurchaseFileViewData()
     * @return array{columns: list<array<string, mixed>>, row_count: int}
     */
    public static function build(PurchaseFile $file, array $data): array
    {
        $columns = [];

        $commissionRows = [];
        foreach ($file->dealers as $dealer) {
            $amount = (float) ($dealer->pivot->commission_rs ?? 0);
            $commissionRows[] = [
                'id' => 'dealer_'.$dealer->id,
                'display' => self::formatCell($amount),
                'amount' => $amount,
                'label' => self::sanitizeUtf8($dealer->name),
            ];
        }
        if ($commissionRows !== []) {
            $columns[] = self::column('commission', 'Commission', $commissionRows);
        }

        foreach ($data['expenseGroups'] as $group) {
            $rows = [];
            foreach ($group['entries'] as $entry) {
                /** @var DayBookEntry $entry */
                $amount = (float) $entry->amount;
                if ($entry->type === DayBookEntry::TYPE_CASH_IN) {
                    $amount = -$amount;
                }
                $rows[] = [
                    'id' => 'entry_'.$entry->id,
                    'display' => self::formatCell($amount),
                    'amount' => $amount,
                ];
            }
            $subCategoryName = self::sanitizeUtf8((string) $group['sub_category']);
            $columns[] = self::column(
                'expense_'.$group['sub_category_id'],
                self::shortExpenseLabel($subCategoryName),
                $rows,
                $subCategoryName
            );
        }

        $amountRows = [];
        foreach ($data['sellers'] as $seller) {
            $amount = (float) $seller->line_total_rs;
            $amountRows[] = [
                'id' => 'seller_'.$seller->id,
                'display' => self::formatCell($amount),
                'amount' => $amount,
                'label' => self::sanitizeUtf8($seller->party?->name),
            ];
        }
        if ($amountRows !== []) {
            $columns[] = self::column('amount', 'Amount', $amountRows);
        }

        $payRows = [];
        if (! empty($data['includePaymentOpening']) && (float) $data['landTotalRs'] > 0) {
            $payRows[] = [
                'id' => 'opening',
                'display' => self::formatCell((float) $data['landTotalRs']),
                'amount' => (float) $data['landTotalRs'],
            ];
        }
        foreach ($data['paymentRows'] as $row) {
            $balance = (float) $row['balance'];
            $payRows[] = [
                'id' => 'payment_'.$row['entry']->id,
                'display' => self::formatCell($balance),
                'amount' => $balance,
            ];
        }
        if ($payRows === [] && (float) ($data['balancePayable'] ?? 0) !== 0.0) {
            $payRows[] = [
                'id' => 'balance',
                'display' => self::formatCell((float) $data['balancePayable']),
                'amount' => (float) $data['balancePayable'],
            ];
        }
        $columns[] = self::column('balance_payable', 'B. Payable', $payRows, null, (float) $data['balancePayable']);

        $columns[] = self::column('grand_total_exp', 'Gr. Total Ex.', [], null, (float) $data['totalExpenses']);

        $rowCount = 0;
        foreach ($columns as $column) {
            $rowCount = max($rowCount, count($column['rows']));
        }

        return [
            'columns' => $columns,
            'row_count' => $rowCount,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function column(string $key, string $label, array $rows, ?string $fullLabel = null, ?float $totalOverride = null): array
    {
        $total = $totalOverride ?? (float) collect($rows)->sum('amount');

        return [
            'key' => $key,
            'label' => self::sanitizeUtf8($label),
            'full_label' => self::sanitizeUtf8($fullLabel ?? $label),
            'rows' => $rows,
            'total' => $total,
            'total_display' => self::formatCell($total),
        ];
    }

    public static function sanitizeUtf8(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $clean = iconv('UTF-8', 'UTF-8//IGNORE', $value);

        return is_string($clean) ? $clean : '';
    }

    public static function shortExpenseLabel(string $name): string
    {
        $lower = strtolower(trim(self::sanitizeUtf8($name)));

        return match (true) {
            str_contains($lower, 'gain tax') => 'G.T.',
            str_contains($lower, 'holding tax') => 'H.T.',
            str_contains($lower, 'registry') => 'Registry',
            str_contains($lower, 'commission') => 'Commission',
            default => self::abbreviate($name),
        };
    }

    private static function abbreviate(string $name): string
    {
        $trimmed = trim($name);
        if (strlen($trimmed) <= 12) {
            return $trimmed;
        }

        return rtrim(substr($trimmed, 0, 10)).'.';
    }

    public static function formatCell(float $amount): string
    {
        $formatted = number_format(abs($amount), 0);
        if ($amount < 0) {
            return '-'.$formatted;
        }

        return $formatted;
    }

    /**
     * @param  array{columns: list<array<string, mixed>>, row_count: int}  $grid
     * @return array{columns: list<array<string, mixed>>, row_count: int}
     */
    public static function filter(array $grid, array $selectedColumnKeys, array $selectedItemsByColumn): array
    {
        $columns = [];
        foreach ($grid['columns'] as $column) {
            $key = $column['key'];
            if ($key === 'grand_total_exp' || ! in_array($key, $selectedColumnKeys, true)) {
                continue;
            }

            $columns[] = self::filterColumn($column, $selectedItemsByColumn[$key] ?? null);
        }

        if (in_array('grand_total_exp', $selectedColumnKeys, true)) {
            $expenseTotal = 0.0;
            foreach ($columns as $column) {
                if (str_starts_with($column['key'], 'expense_')) {
                    $expenseTotal += (float) $column['total'];
                }
            }
            $grand = collect($grid['columns'])->firstWhere('key', 'grand_total_exp');
            if ($grand) {
                $grand['total'] = $expenseTotal;
                $grand['total_display'] = self::formatCell($expenseTotal);
                $grand['rows'] = [];
                $columns[] = $grand;
            }
        }

        $rowCount = 0;
        foreach ($columns as $column) {
            $rowCount = max($rowCount, count($column['rows']));
        }

        return [
            'columns' => $columns,
            'row_count' => $rowCount,
        ];
    }

    /**
     * @param  list<string>|null  $allowedIds
     * @return array<string, mixed>
     */
    private static function filterColumn(array $column, ?array $allowedIds): array
    {
        $rows = $column['rows'];
        if (is_array($allowedIds) && $allowedIds !== []) {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row) => in_array($row['id'], $allowedIds, true)
            ));
        }

        $totalOverride = null;
        if ($column['key'] === 'balance_payable' && $rows !== []) {
            $last = end($rows);
            $totalOverride = (float) ($last['amount'] ?? $column['total']);
        }

        $filtered = self::column(
            $column['key'],
            $column['label'],
            $rows,
            $column['full_label'],
            $totalOverride
        );
        $filtered['full_label'] = $column['full_label'];

        return $filtered;
    }
}
