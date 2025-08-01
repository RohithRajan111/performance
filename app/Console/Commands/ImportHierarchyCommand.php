<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Spatie\Permission\Models\Role;

class ImportHierarchyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-from-excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports users, roles, and manager relationships from an Excel file.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = storage_path('app/company_data.xlsx');

        if (!file_exists($filePath)) {
            $this->error("FATAL: File not found at: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("File found. Starting import...");

        $reader = new Xlsx();
        $spreadsheet = $reader->load($filePath);
        $excelData = $spreadsheet->getActiveSheet()->toArray();
        array_shift($excelData); // Remove header row

        DB::beginTransaction();
        try {
            // --- PASS 1: CREATE USERS AND ASSIGN ROLES ---
            $this->info('--- Pass 1: Creating user records and assigning roles ---');
            foreach ($excelData as $rowNumber => $row) {
                $name = $row[0] ?? null;
                if (empty($name)) continue;

                $email = strtolower(str_replace(' ', '.', $name)) . '@company.com';
                if (User::where('email', $email)->exists()) {
                    $this->warn("User '{$name}' with email '{$email}' already exists. Skipping.");
                    continue;
                }

                // =========================================================================
                // === NEW & IMPROVED DATE PARSING FUNCTION ===
                // =========================================================================
                $parseDate = function($dateString) use ($name, $rowNumber) {
                    if (empty(trim($dateString))) {
                        return null;
                    }

                    // An array of date formats to try in order of priority.
                    $formats = [
                        'd/m/Y', // Format: 25/07/2024
                        'd-M-Y', // Format: 28-Jun-1990
                        'd-M',   // NEW: Handles birthday format like 27-Oct
                    ];

                    foreach ($formats as $format) {
                        try {
                            // Attempt to create a Carbon date object from the given format.
                            $date = Carbon::createFromFormat($format, trim($dateString));

                            // ** Special handling for year-less birthday format **
                            if ($format === 'd-M') {
                                // If the format was d-M, the year is missing. We'll set a placeholder.
                                // The year 2000 is a common convention for this.
                                $date->setYear(2000);
                            }

                            // If parsing was successful, return the date in the database format.
                            return $date->format('Y-m-d');

                        } catch (Exception $e) {
                            // This format failed, the loop will automatically try the next one.
                            continue;
                        }
                    }

                    // If the loop completes without returning, none of the formats matched.
                    $this->warn("Could not parse date '{$dateString}' for user '{$name}' on row " . ($rowNumber + 2) . ". Setting to NULL.");
                    return null;
                };
                // =========================================================================
                // === END OF NEW DATE PARSING FUNCTION ===
                // =========================================================================

                // Create the user
                $user = User::create([
                    'name'          => $name,
                    'email'         => $email,
                    'password'      => Hash::make('password'),
                    'designation'   => $row[1] ?? null,
                    'work_mode'     => trim($row[5] ?? null),
                    'hire_date'     => $parseDate($row[2] ?? null), // Corresponds to DOJ
                    'birth_date'    => $parseDate($row[3] ?? null), // Now handles 27-Oct
                    'leave_balance' => 20.00,
                    'parent_id'     => null,
                ]);

                $this->line("Created user: {$user->name}");

                // Role Assignment Logic
                $processedRoleName = strtolower(trim($row[6] ?? ''));
                if (!empty($processedRoleName)) {
                    $role = Role::where('name', $processedRoleName)->first();
                    if ($role) {
                        $user->assignRole($role);
                        $this->line(" -> SUCCESS: Role '{$role->name}' found and assigned.");
                    } else {
                        $this->warn(" -> WARNING: Role '{$processedRoleName}' was not found in the database.");
                    }
                } else {
                     $this->warn(" -> WARNING: No role specified in column G for {$user->name}.");
                }
            }

            // Pass 2 remains the same...
            $this->info('--- Pass 2: Linking managers ---');
            $allUsers = User::all();
            foreach ($excelData as $row) {
                $employeeName = $row[0] ?? null;
                $managerName = $row[4] ?? null;
                if (empty($employeeName) || empty($managerName)) continue;
                $employeeUser = $allUsers->firstWhere('name', $employeeName);
                $managerUser = $allUsers->firstWhere('name', $managerName);
                if ($employeeUser && $managerUser) {
                    $employeeUser->update(['parent_id' => $managerUser->id]);
                    $this->line("Linked {$employeeName} -> {$managerName}");
                } else if (!$managerUser) {
                    $this->warn("Could not create link for '{$employeeName}'. Manager '{$managerName}' was not found.");
                }
            }

            DB::commit();
            $this->info('------------------------------------');
            $this->info('User import completed successfully! Data has been saved.');
            return Command::SUCCESS;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('A critical error occurred. Transaction rolled back.');
            $this->error('Error Message: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ' on line ' . $e->getLine());
            return Command::FAILURE;
        }
    }
}
