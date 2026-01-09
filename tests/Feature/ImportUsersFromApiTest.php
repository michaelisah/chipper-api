<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportUsersFromApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_imports_users_from_api()
    {
        // Mock the HTTP response
        Http::fake([
            'https://jsonplaceholder.typicode.com/users' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'username' => 'Bret',
                    'email' => 'Sincere@april.biz',
                    'address' => [
                        'street' => 'Kulas Light',
                        'suite' => 'Apt. 556',
                        'city' => 'Gwenborough',
                        'zipcode' => '92998-3874',
                        'geo' => [
                            'lat' => '-37.3159',
                            'lng' => '81.1496'
                        ]
                    ],
                    'phone' => '1-770-736-8031 x56442',
                    'website' => 'hildegard.org',
                    'company' => [
                        'name' => 'Romaguera-Crona',
                        'catchPhrase' => 'Multi-layered client-server neural-net',
                        'bs' => 'harness real-time e-markets'
                    ]
                ],
                [
                    'id' => 2,
                    'name' => 'Ervin Howell',
                    'username' => 'Antonette',
                    'email' => 'Shanna@melissa.tv',
                    'address' => [
                        'street' => 'Victor Plains',
                        'suite' => 'Suite 879',
                        'city' => 'Wisokyburgh',
                        'zipcode' => '90566-7771',
                        'geo' => [
                            'lat' => '-43.9509',
                            'lng' => '-34.4618'
                        ]
                    ],
                    'phone' => '010-692-6593 x09125',
                    'website' => 'anastasia.net',
                    'company' => [
                        'name' => 'Deckow-Crist',
                        'catchPhrase' => 'Proactive didactic contingency',
                        'bs' => 'synergize scalable supply-chains'
                    ]
                ]
            ], 200)
        ]);

        // Run the command
        $this->artisan('users:import-from-api')
            ->expectsOutput('Importing users from https://jsonplaceholder.typicode.com/users')
            ->expectsOutput('Found 2 users to import.')
            ->assertExitCode(0);

        // Assert users were created
        $this->assertDatabaseHas('users', [
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);
    }

    public function test_command_skips_existing_users()
    {
        // Create a user that already exists
        User::factory()->create([
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);

        // Mock the HTTP response
        Http::fake([
            'https://jsonplaceholder.typicode.com/users' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'email' => 'Sincere@april.biz',
                ],
                [
                    'id' => 2,
                    'name' => 'Ervin Howell',
                    'email' => 'Shanna@melissa.tv',
                ]
            ], 200)
        ]);

        // Run the command
        $this->artisan('users:import-from-api')
            ->expectsOutput('Importing users from https://jsonplaceholder.typicode.com/users')
            ->expectsOutput('Found 2 users to import.')
            ->assertExitCode(0);

        // Assert only one new user was created (the other was skipped)
        $this->assertEquals(2, User::count());
        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);
    }

    public function test_command_handles_api_error()
    {
        // Mock a failed HTTP response
        Http::fake([
            'https://jsonplaceholder.typicode.com/users' => Http::response(null, 500)
        ]);

        // Run the command
        $this->artisan('users:import-from-api')
            ->expectsOutput('Importing users from https://jsonplaceholder.typicode.com/users')
            ->expectsOutput('Failed to fetch users from the API: 500')
            ->assertExitCode(1);

        // Assert no users were created
        $this->assertEquals(0, User::count());
    }

    public function test_command_respects_limit_option()
    {
        // Mock the HTTP response
        Http::fake([
            'https://jsonplaceholder.typicode.com/users' => Http::response([
                [
                    'id' => 1,
                    'name' => 'Leanne Graham',
                    'email' => 'Sincere@april.biz',
                ],
                [
                    'id' => 2,
                    'name' => 'Ervin Howell',
                    'email' => 'Shanna@melissa.tv',
                ],
                [
                    'id' => 3,
                    'name' => 'Clementine Bauch',
                    'email' => 'Nathan@yesenia.net',
                ]
            ], 200)
        ]);

        // Run the command with limit option
        $this->artisan('users:import-from-api --limit=2')
            ->expectsOutput('Importing users from https://jsonplaceholder.typicode.com/users')
            ->expectsOutput('Limiting import to 2 users.')
            ->expectsOutput('Found 2 users to import.')
            ->assertExitCode(0);

        // Assert only 2 users were created
        $this->assertEquals(2, User::count());
        $this->assertDatabaseHas('users', [
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);
        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);
        $this->assertDatabaseMissing('users', [
            'name' => 'Clementine Bauch',
            'email' => 'Nathan@yesenia.net',
        ]);
    }
}
