<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('purchase_file_id')
                ->nullable()
                ->after('project_id')
                ->constrained('purchase_files')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('purchase_items', 'project_file_id')) {
            $rows = DB::table('purchase_items')
                ->whereNotNull('project_file_id')
                ->select('id', 'project_id', 'project_file_id')
                ->get();

            foreach ($rows as $row) {
                $pf = DB::table('project_files')->where('id', $row->project_file_id)->first();
                if (! $pf) {
                    continue;
                }

                $purchaseFileId = DB::table('purchase_files')
                    ->where('project_id', $pf->project_id)
                    ->where('file_name', $pf->file_number)
                    ->value('id');

                if (! $purchaseFileId) {
                    $purchaseFileId = DB::table('purchase_files')->insertGetId([
                        'project_id' => $pf->project_id,
                        'file_name' => $pf->file_number,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    if ($pf->dealer_party_id) {
                        DB::table('purchase_file_dealers')->insertOrIgnore([
                            'purchase_file_id' => $purchaseFileId,
                            'party_id' => $pf->dealer_party_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('purchase_items')->where('id', $row->id)->update([
                    'purchase_file_id' => $purchaseFileId,
                ]);
            }

            Schema::table('purchase_items', function (Blueprint $table) {
                $table->dropConstrainedForeignId('project_file_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->foreignId('project_file_id')
                ->nullable()
                ->after('project_id')
                ->constrained('project_files')
                ->nullOnDelete();
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_file_id');
        });
    }
};
