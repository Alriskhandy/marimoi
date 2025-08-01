<?php

// Console/Commands/MigrateToUnifiedTables.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\DataSpatial;

class MigrateToUnifiedTables extends Command
{
    protected $signature = 'migrate:unified-tables 
                           {--dry-run : Show what would be migrated without actually doing it}
                           {--force : Skip confirmation prompts}
                           {--batch-size=100 : Number of records to process at once}';
    
    protected $description = 'Migrate data from old separated tables to unified data_spatial and categories tables';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        
        if ($isDryRun) {
            $this->info('=== DRY RUN MODE - No actual migration will be performed ===');
        }

        $this->info('🚀 Starting migration to unified tables...');

        // Check if unified tables exist
        if (!$this->checkUnifiedTablesExist()) {
            $this->error('❌ Unified tables (data_spatial, categories) do not exist!');
            $this->error('Please run: php artisan migrate');
            return 1;
        }

        // Warning about data loss
        if (!$isDryRun && !$this->option('force')) {
            if (!$this->confirm('⚠️  This will migrate data to unified tables. Continue?')) {
                $this->info('Migration cancelled.');
                return 0;
            }
        }

        try {
            // Step 1: Migrate categories
            $categoriesCount = $this->migrateCategories($isDryRun, $batchSize);

            // Step 2: Migrate data spatial
            $dataCount = $this->migrateDataSpatial($isDryRun, $batchSize);

            if ($isDryRun) {
                $this->info('=== DRY RUN COMPLETED ===');
                $this->table(['Type', 'Count'], [
                    ['Categories', $categoriesCount],
                    ['Data Spatial', $dataCount],
                    ['Total', $categoriesCount + $dataCount]
                ]);
            } else {
                $this->info('✅ Migration completed successfully!');
                $this->table(['Type', 'Migrated'], [
                    ['Categories', $categoriesCount],
                    ['Data Spatial', $dataCount],
                    ['Total', $categoriesCount + $dataCount]
                ]);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Migration failed: ' . $e->getMessage());
            return 1;
        }
    }

    private function checkUnifiedTablesExist()
    {
        return Schema::hasTable('categories') && Schema::hasTable('data_spatial');
    }

    private function migrateCategories($isDryRun, $batchSize)
    {
        $this->info('📁 Migrating categories...');

        $categoryMappings = [
            'kategori_layers' => 'layers',
            'kategori_musenbangs' => 'musenbangs',
            'kategori_pokir_dprds' => 'pokir_dprds',
            'kategori_psd' => 'psd',
            'kategori_psn' => 'psn'
        ];

        $totalMigrated = 0;

        foreach ($categoryMappings as $oldTable => $type) {
            if (!Schema::hasTable($oldTable)) {
                $this->warn("  ⚠️  Table {$oldTable} does not exist, skipping...");
                continue;
            }

            $totalRecords = DB::table($oldTable)->count();
            
            if ($totalRecords === 0) {
                $this->info("  ℹ️  No records found in {$oldTable}");
                continue;
            }

            if ($isDryRun) {
                $this->info("  📊 Would migrate {$totalRecords} categories from {$oldTable} as type '{$type}'");
                $totalMigrated += $totalRecords;
                continue;
            }

            $this->info("  🔄 Migrating {$totalRecords} categories from {$oldTable}...");
            $bar = $this->output->createProgressBar($totalRecords);
            $bar->start();

            $offset = 0;
            $migrated = 0;

            while ($offset < $totalRecords) {
                $records = DB::table($oldTable)
                           ->offset($offset)
                           ->limit($batchSize)
                           ->get();

                foreach ($records as $record) {
                    try {
                        Category::create([
                            'id' => $record->id,
                            'type' => $type,
                            'nama' => $record->nama ?? null,
                            'warna' => $record->warna ?? null,
                            'icon' => $record->icon ?? null,
                            'is_marker' => $record->is_marker ?? false,
                            'deskripsi' => $record->deskripsi ?? null,
                            'parent_id' => $record->parent_id ?? null,
                            'created_at' => $record->created_at ?? now(),
                            'updated_at' => $record->updated_at ?? now(),
                        ]);
                        $migrated++;
                        $bar->advance();
                    } catch (\Exception $e) {
                        $this->error("\n❌ Failed to migrate category ID {$record->id}: " . $e->getMessage());
                        $bar->advance();
                        continue;
                    }
                }

                $offset += $batchSize;
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✅ Migrated {$migrated}/{$totalRecords} categories from {$oldTable}");
            $totalMigrated += $migrated;
        }

        return $totalMigrated;
    }

    private function migrateDataSpatial($isDryRun, $batchSize)
    {
        $this->info('🗺️  Migrating spatial data...');

        $dataMappings = [
            'lokasis' => [
                'data_type' => 'lokasi',
                'sub_type' => null,
                'has_geom' => true,
                'has_tahun' => false
            ],
            'usulan_musenbangs' => [
                'data_type' => 'usulan_musenbang',
                'sub_type' => null,
                'has_geom' => false,
                'has_tahun' => false
            ],
            'pokir_dprds' => [
                'data_type' => 'pokir_dprd',
                'sub_type' => null,
                'has_geom' => false,
                'has_tahun' => false
            ],
            'proyek_strategis_daerahs' => [
                'data_type' => 'proyek_strategis',
                'sub_type' => 'daerah',
                'has_geom' => true,
                'has_tahun' => true
            ],
            'proyek_strategis_nasionals' => [
                'data_type' => 'proyek_strategis',
                'sub_type' => 'nasional',
                'has_geom' => true,
                'has_tahun' => true
            ]
        ];

        $totalMigrated = 0;

        foreach ($dataMappings as $oldTable => $config) {
            if (!Schema::hasTable($oldTable)) {
                $this->warn("  ⚠️  Table {$oldTable} does not exist, skipping...");
                continue;
            }

            $totalRecords = DB::table($oldTable)->count();
            
            if ($totalRecords === 0) {
                $this->info("  ℹ️  No records found in {$oldTable}");
                continue;
            }

            if ($isDryRun) {
                $this->info("  📊 Would migrate {$totalRecords} records from {$oldTable} as '{$config['data_type']}'");
                if ($config['sub_type']) {
                    $this->info("    Sub type: {$config['sub_type']}");
                }
                $totalMigrated += $totalRecords;
                continue;
            }

            $this->info("  🔄 Migrating {$totalRecords} records from {$oldTable}...");
            $bar = $this->output->createProgressBar($totalRecords);
            $bar->start();

            $offset = 0;
            $migrated = 0;

            while ($offset < $totalRecords) {
                $records = DB::table($oldTable)
                           ->offset($offset)
                           ->limit($batchSize)
                           ->get();

                foreach ($records as $record) {
                    try {
                        $data = [
                            'id' => $record->id,
                            'data_type' => $config['data_type'],
                            'sub_type' => $config['sub_type'],
                            'kategori_id' => $record->kategori_id ?? null,
                            'deskripsi' => $record->deskripsi ?? null,
                            'dbf_attributes' => $record->dbf_attributes ?? null,
                            'created_at' => $record->created_at ?? now(),
                            'updated_at' => $record->updated_at ?? now(),
                        ];

                        // Add tahun if exists
                        if ($config['has_tahun'] && isset($record->tahun)) {
                            $data['tahun'] = $record->tahun;
                        }

                        // Handle geometry
                        if ($config['has_geom'] && isset($record->geom)) {
                            // For PostgreSQL with PostGIS
                            DB::table('data_spatial')->insert(array_merge($data, [
                                'geom' => DB::raw("'{$record->geom}'::geometry")
                            ]));
                        } else {
                            DataSpatial::create($data);
                        }

                        $migrated++;
                        $bar->advance();
                    } catch (\Exception $e) {
                        $this->error("\n❌ Failed to migrate data ID {$record->id}: " . $e->getMessage());
                        $bar->advance();
                        continue;
                    }
                }

                $offset += $batchSize;
            }

            $bar->finish();
            $this->newLine();
            $this->info("  ✅ Migrated {$migrated}/{$totalRecords} records from {$oldTable}");
            $totalMigrated += $migrated;
        }

        return $totalMigrated;
    }
}
