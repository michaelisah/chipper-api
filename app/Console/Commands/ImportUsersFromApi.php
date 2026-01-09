<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportUsersFromApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-from-api 
                            {url=https://jsonplaceholder.typicode.com/users : The URL of the JSON API}
                            {--limit= : Limit the number of users to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        $limit = $this->option('limit');
        
        $this->info("Importing users from {$url}");
        
        try {
            // Fetch users from the API
            $response = Http::get($url);
            
            if ($response->failed()) {
                $this->error("Failed to fetch users from the API: {$response->status()}");
                return 1;
            }
            
            $users = $response->json();
            
            if (empty($users)) {
                $this->warn("No users found in the API response.");
                return 0;
            }
            
            // Apply limit if specified
            if ($limit && is_numeric($limit)) {
                $users = array_slice($users, 0, (int) $limit);
                $this->info("Limiting import to {$limit} users.");
            }
            
            $this->info("Found " . count($users) . " users to import.");
            
            $importedCount = 0;
            $skippedCount = 0;
            $errorCount = 0;
            
            $progressBar = $this->output->createProgressBar(count($users));
            $progressBar->start();
            
            foreach ($users as $userData) {
                // Validate user data
                $validator = Validator::make($userData, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255',
                ]);
                
                if ($validator->fails()) {
                    $this->newLine();
                    $this->warn("Skipping invalid user data: " . json_encode($userData));
                    $errorCount++;
                    $progressBar->advance();
                    continue;
                }
                
                // Check if user already exists
                $existingUser = User::where('email', $userData['email'])->first();
                
                if ($existingUser) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }
                
                // Create new user
                try {
                    User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => Hash::make(Str::random(16)), // Generate a random password
                        'email_verified_at' => now(),
                    ]);
                    
                    $importedCount++;
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Error creating user {$userData['email']}: " . $e->getMessage());
                    $errorCount++;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info("Import completed:");
            $this->info("- Imported: {$importedCount} users");
            $this->info("- Skipped (already exist): {$skippedCount} users");
            $this->info("- Errors: {$errorCount} users");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("An error occurred: " . $e->getMessage());
            return 1;
        }
    }
}
