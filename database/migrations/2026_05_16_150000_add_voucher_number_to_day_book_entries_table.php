<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->unsignedInteger('voucher_no')->nullable()->unique()->after('entry_date');
        });

        $byYear = [];
        $rows = DB::table('day_book_entries')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get(['id', 'entry_date']);

        foreach ($rows as $row) {
            $year = (int) date('y', strtotime($row->entry_date));
            $byYear[$year] = ($byYear[$year] ?? 0) + 1;
            DB::table('day_book_entries')->where('id', $row->id)->update([
                'voucher_no' => ($year * 10000) + $byYear[$year],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropUnique(['voucher_no']);
            $table->dropColumn('voucher_no');
        });
    }
};
