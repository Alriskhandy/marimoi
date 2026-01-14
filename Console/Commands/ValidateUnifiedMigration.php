<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateUnifiedMigration extends Command
{
    protected $signature = 'validate:unified-migration 
                           {--detailed : Show detailed validation results}';
    
    protected $description = 'Validate data after migration to unified tables';

    public function handle()
    {
        $this->info('🔍 Validating unified migration...');

        $detailed = $this->option('detailed');
        $isValid = true;

        // Check table existence
        if (!Schema::hasTable('categories') || !Schema::hasTable('data_spatial')) {
            $this->error('❌ Unified tables do not exist!');
            return 1;
        }

        // Validate categories
        $categoryValidation = $this->validateCategories($detailed);
        $isValid = $isValid && $categoryValidation;

        // Validate data spatial
        $dataValidation = $this->validateDataSpatial($detailed);
        $isValid = $isValid && $dataValidation;

        // Validate relationships
        $relationshipValidation = $this->validateRelationships($detailed);
        $isValid = $isValid && $relationshipValidation;

        // Summary
        if ($isValid) {
            $this->info('✅ All validations passed! Migration appears successful.');
            return 0;
        } else {
            $this->error('❌ Some validations failed. Please review the issues above.');
            return 1;
        }
    }

    private function validateCategories($detailed)
    {
        $this->info('📁 Validating categories...');

        $stats = DB::table('categories')
                   ->select('type', DB::raw('count(*) as total'))
                   ->groupBy('type')
                   ->get();

        if ($detailed) {
            $this->table(['Category Type', 'Count'], $stats->map(function($stat) {
                return [$stat->type, $stat->total];
            })->toArray());
        }

        // Check for orphaned categories
        $orphans = DB::table('categories')
                     ->whereNotNull('parent_id')
                     ->whereNotExists(function($query) {
                         $query->select(DB::raw(1))
                               ->from('categories as parent')
                               ->whereRaw('parent.id = categories.parent_id');
                     })
                     ->count();

        if ($orphans > 0) {
            $this->warn("⚠️  Found {$orphans} orphaned categories");
            return false;
        } else {
            $this->info("✅ No orphaned categories found");
        }

        // Check for invalid types
        $validTypes = ['layers', 'musrenbang', 'pokir_dprds', 'psd', 'psn'];
        $invalidTypes = DB::table('categories')
                          ->whereNotIn('type', $validTypes)
                          ->count();

        if ($invalidTypes > 0) {
            $this->warn("⚠️  Found {$invalidTypes} categories with invalid types");
            
            if ($detailed) {
                $invalid = DB::table('categories')
                            ->select('id', 'name', 'type')
                            ->whereNotIn('type', $validTypes)
                            ->get();
                
                $this->table(['ID', 'Name', 'Invalid Type'], $invalid->map(function($item) {
                    return [$item->id, $item->name, $item->type];
                })->toArray());
            }
            return false;
        } else {
            $this->info("✅ All category types are valid");
        }

        // Check for duplicate names within same type and level
        $duplicates = DB::table('categories')
                        ->select('name', 'type', 'parent_id', DB::raw('count(*) as count'))
                        ->groupBy('name', 'type', 'parent_id')
                        ->having('count', '>', 1)
                        ->get();

        if ($duplicates->count() > 0) {
            $this->warn("⚠️  Found {$duplicates->count()} duplicate category names");
            
            if ($detailed) {
                $this->table(['Name', 'Type', 'Parent ID', 'Count'], $duplicates->map(function($dup) {
                    return [$dup->name, $dup->type, $dup->parent_id ?? 'NULL', $dup->count];
                })->toArray());
            }
            return false;
        } else {
            $this->info("✅ No duplicate category names found");
        }

        return true;
    }

    private function validateDataSpatial($detailed)
    {
        $this->info('🗺️  Validating data spatial...');

        $stats = DB::table('data_spatial')
                   ->select('type', DB::raw('count(*) as total'))
                   ->groupBy('type')
                   ->get();

        if ($detailed) {
            $this->table(['Data Type', 'Count'], $stats->map(function($stat) {
                return [$stat->type, $stat->total];
            })->toArray());
        }

        // Check for invalid types
        $validTypes = ['layers', 'musrenbang', 'pokir_dprds', 'psd', 'psn'];
        $invalidTypes = DB::table('data_spatial')
                          ->whereNotIn('type', $validTypes)
                          ->count();

        if ($invalidTypes > 0) {
            $this->warn("⚠️  Found {$invalidTypes} data spatial records with invalid types");
            return false;
        } else {
            $this->info("✅ All data spatial types are valid");
        }

        // Check for missing required fields
        $missingTitle = DB::table('data_spatial')
                          ->where(function($query) {
                              $query->whereNull('title')
                                    ->orWhere('title', '');
                          })
                          ->count();

        if ($missingTitle > 0) {
            $this->warn("⚠️  Found {$missingTitle} records with missing titles");
            return false;
        } else {
            $this->info("✅ All records have titles");
        }

        // Check for invalid geometry data
        $invalidGeometry = DB::table('data_spatial')
                             ->where(function($query) {
                                 $query->whereNull('geometry')
                                       ->orWhere('geometry', '');
                             })
                             ->count();

        if ($invalidGeometry > 0) {
            $this->warn("⚠️  Found {$invalidGeometry} records with missing geometry");
            return false;
        } else {
            $this->info("✅ All records have geometry data");
        }

        // Check for records with invalid JSON in properties
        $invalidProperties = 0;
        $records = DB::table('data_spatial')
                     ->whereNotNull('properties')
                     ->select('id', 'properties')
                     ->chunk(1000, function($chunk) use (&$invalidProperties) {
                         foreach ($chunk as $record) {
                             if (!empty($record->properties)) {
                                 $decoded = json_decode($record->properties, true);
                                 if (json_last_error() !== JSON_ERROR_NONE) {
                                     $invalidProperties++;
                                 }
                             }
                         }
                     });

        if ($invalidProperties > 0) {
            $this->warn("⚠️  Found {$invalidProperties} records with invalid JSON properties");
            return false;
        } else {
            $this->info("✅ All properties have valid JSON format");
        }

        return true;
    }

    private function validateRelationships($detailed)
    {
        $this->info('🔗 Validating relationships...');

        // Check data_spatial records have valid category references
        $invalidCategoryRefs = DB::table('data_spatial')
                                 ->whereNotNull('category_id')
                                 ->whereNotExists(function($query) {
                                     $query->select(DB::raw(1))
                                           ->from('categories')
                                           ->whereRaw('categories.id = data_spatial.category_id');
                                 })
                                 ->count();

        if ($invalidCategoryRefs > 0) {
            $this->warn("⚠️  Found {$invalidCategoryRefs} data spatial records with invalid category references");
            
            if ($detailed) {
                $invalid = DB::table('data_spatial')
                            ->select('id', 'title', 'category_id')
                            ->whereNotNull('category_id')
                            ->whereNotExists(function($query) {
                                $query->select(DB::raw(1))
                                      ->from('categories')
                                      ->whereRaw('categories.id = data_spatial.category_id');
                            })
                            ->limit(10)
                            ->get();

                $this->table(['ID', 'Title', 'Invalid Category ID'], $invalid->map(function($item) {
                    return [$item->id, $item->title, $item->category_id];
                })->toArray());
            }
            return false;
        } else {
            $this->info("✅ All data spatial category references are valid");
        }

        // Check type consistency between data_spatial and categories
        $typeInconsistency = DB::table('data_spatial as ds')
                               ->join('categories as c', 'ds.category_id', '=', 'c.id')
                               ->where('ds.type', '!=', DB::raw('c.type'))
                               ->count();

        if ($typeInconsistency > 0) {
            $this->warn("⚠️  Found {$typeInconsistency} records with type inconsistency between data_spatial and categories");
            
            if ($detailed) {
                $inconsistent = DB::table('data_spatial as ds')
                                  ->join('categories as c', 'ds.category_id', '=', 'c.id')
                                  ->select('ds.id', 'ds.title', 'ds.type as data_type', 'c.type as category_type')
                                  ->where('ds.type', '!=', DB::raw('c.type'))
                                  ->limit(10)
                                  ->get();

                $this->table(['ID', 'Title', 'Data Type', 'Category Type'], $inconsistent->map(function($item) {
                    return [$item->id, $item->title, $item->data_type, $item->category_type];
                })->toArray());
            }
            return false;
        } else {
            $this->info("✅ All type relationships are consistent");
        }

        // Check for data without categories (if required)
        $uncategorized = DB::table('data_spatial')
                           ->whereNull('category_id')
                           ->count();

        if ($uncategorized > 0) {
            $this->warn("⚠️  Found {$uncategorized} data spatial records without categories");
            
            if ($detailed) {
                $uncat = DB::table('data_spatial')
                           ->select('id', 'title', 'type')
                           ->whereNull('category_id')
                           ->limit(10)
                           ->get();

                $this->table(['ID', 'Title', 'Type'], $uncat->map(function($item) {
                    return [$item->id, $item->title, $item->type];
                })->toArray());
            }
            
            // This might be a warning rather than an error depending on business rules
            $this->info("ℹ️  This might be acceptable depending on your business rules");
        } else {
            $this->info("✅ All data spatial records have categories");
        }

        return true;
    }
}