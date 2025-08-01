<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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
    protected $description = 'Imports users from an Excel file into the users table, linking managers by name.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = storage_path('app/company_data.xlsx');

        if (! file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");

            return 1;
        }

        $this->info('Starting user import from Excel...');

        $reader = new Xlsx;
        $spreadsheet = $reader->load($filePath);
        $excelData = $spreadsheet->getActiveSheet()->toArray();
        array_shift($excelData); // Remove header row

        // Optional: Uncomment to clear old data before importing
        // DB::table('users')->where('id', '>', 1)->delete();
        // $this->warn('Cleared existing user data (except user ID 1).');

        DB::beginTransaction();
        try {
            // --- PASS 1: INSERT ALL USERS ---
            $this->info('--- Pass 1: Creating user records ---');
            foreach ($excelData as $rowNumber => $row) {
                $name = $row[0] ?? null;
                if (empty($name)) {
                    continue;
                }

                $email = strtolower(str_replace(' ', '.', $name)).'@company.com';
                if (DB::table('users')->where('email', $email)->exists()) {
                    $this->warn("User '{$name}' already exists. Skipping insertion.");

                    continue;
                }

                // --- ROBUST DATE PARSING LOGIC ---
                // This function will try multiple formats
                $parseDate = function ($dateString) {
                    if (empty($dateString)) {
                        return null;
                    }
                    // Try format 1: dd/mm/yyyy (e.g., 28/06/1990)
                    try {
                        return Carbon::createFromFormat('d/m/Y', $dateString)->format('Y-m-d');
                    } catch (Exception $e) {
                        // try format 2: d-M-Y (e.g., 28-Jun-1990)
                        try {
                            return Carbon::createFromFormat('d-M-Y', $dateString)->format('Y-m-d');
                        } catch (Exception $e2) {
                            // If both fail, return null
                            return null;
                        }
                    }
                };

                $hire_date = $parseDate($row[2] ?? null);
                $birth_date = $parseDate($row[3] ?? null);

                if (empty($hire_date)) {
                    $this->warn("Could not parse hire date for '{$name}' on row ".($rowNumber + 2).'. Setting to NULL.');
                }
                if (empty($birth_date)) {
                    $this->warn("Could not parse birth date for '{$name}' on row ".($rowNumber + 2).'. Setting to NULL.');
                }
                // --- END OF ROBUST DATE PARSING ---

                DB::table('users')->insert([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'designation' => $row[1] ?? null,
                    'hire_date' => $hire_date,
                    'birth_date' => $birth_date,
                    'leave_balance' => 20.00,
                    'parent_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->line("Created user: {$name}");
            }

            // --- PASS 2: LINK MANAGERS ---
            $this->info('--- Pass 2: Linking managers by setting parent_id ---');
            $allUsers = DB::table('users')->get(['id', 'name']);

            foreach ($excelData as $row) {
                $employeeName = $row[0] ?? null;
                $managerName = $row[4] ?? null;
                if (empty($employeeName) || empty($managerName)) {
                    continue;
                }

                $employeeUser = $allUsers->firstWhere('name', $employeeName);
                $managerUser = $allUsers->firstWhere('name', $managerName);

                if ($employeeUser && $managerUser) {
                    DB::table('users')->where('id', $employeeUser->id)->update(['parent_id' => $managerUser->id]);
                    $this->line("Linked {$employeeName} -> {$managerName}");
                } else {
                    $this->warn("Could not create link for '{$employeeName}'. Check if manager '{$managerName}' exists.");
                }
            }

            DB::commit();
            $this->info('------------------------------------');
            $this->info('User import completed successfully! Data has been saved.');

            return 0;

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('A critical error occurred. Transaction rolled back. Nothing was saved.');
            $this->error('Error Message: '.$e->getMessage());
            $this->error('File: '.$e->getFile().' on line '.$e->getLine());

            return 1;
        }
    }
}
