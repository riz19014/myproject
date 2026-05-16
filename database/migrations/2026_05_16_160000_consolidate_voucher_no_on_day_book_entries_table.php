<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('day_book_entries', 'voucher_year')) {
            return;
        }

        if (! Schema::hasColumn('day_book_entries', 'voucher_no')) {
            Schema::table('day_book_entries', function (Blueprint $table) {
                $table->unsignedInteger('voucher_no')->nullable()->after('entry_date');
            });
        }

        DB::table('day_book_entries')
            ->whereNotNull('voucher_year')
            ->whereNotNull('voucher_seq')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('day_book_entries')->where('id', $row->id)->update([
                        'voucher_no' => ((int) $row->voucher_year * 10000) + (int) $row->voucher_seq,
                    ]);
                }
            });

        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropUnique(['voucher_year', 'voucher_seq']);
            $table->dropColumn(['voucher_year', 'voucher_seq']);
        });

        if (! $this->indexExists('day_book_entries', 'day_book_entries_voucher_no_unique')) {
            Schema::table('day_book_entries', function (Blueprint $table) {
                $table->unique('voucher_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('day_book_entries', 'voucher_year')) {
            return;
        }

        Schema::table('day_book_entries', function (Blueprint $table) {
            if ($this->indexExists('day_book_entries', 'day_book_entries_voucher_no_unique')) {
                $table->dropUnique(['voucher_no']);
            }
            $table->unsignedTinyInteger('voucher_year')->nullable()->after('entry_date');
            $table->unsignedInteger('voucher_seq')->nullable()->after('voucher_year');
            $table->unique(['voucher_year', 'voucher_seq']);
        });

        DB::table('day_book_entries')
            ->whereNotNull('voucher_no')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $no = (int) $row->voucher_no;
                    DB::table('day_book_entries')->where('id', $row->id)->update([
                        'voucher_year' => intdiv($no, 10000),
                        'voucher_seq' => $no % 10000,
                    ]);
                }
            });

        Schema::table('day_book_entries', function (Blueprint $table) {
            $table->dropColumn('voucher_no');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        return (bool) $connection->table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->count();
    }
};
