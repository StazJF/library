<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\SystemUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BookImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_leading_blank_and_header_rows(): void
    {
        $admin = SystemUser::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'employee_id' => 'EMP-001',
        ]);

        $this->actingAs($admin);

        $csv = "\nTitle,Author,Publisher,ISBN,Category,Copies\n"
            . "My Book,Jane Doe,Acme,9780000000001,Learning Area,3\n";

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $response = $this->post(route('books.import.post'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('books.catalog'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('books', [
            'isbn' => '9780000000001',
            'title' => 'My Book',
            'copies' => 3,
        ]);
    }

    public function test_import_skips_rows_with_non_numeric_copies(): void
    {
        $admin = SystemUser::create([
            'email' => 'admin2@example.com',
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'employee_id' => 'EMP-002',
        ]);

        $this->actingAs($admin);

        $csv = "Title,Author,Publisher,ISBN,Category,Copies\n"
            . "Good Book,Jane Doe,Acme,9780000000002,Learning Area,2\n"
            . "Bad Book,John Doe,Acme,9780000000003,Learning Area,Copies\n";

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $response = $this->post(route('books.import.post'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('books.catalog'));
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('books', [
            'isbn' => '9780000000002',
            'title' => 'Good Book',
            'copies' => 2,
        ]);

        $this->assertDatabaseMissing('books', [
            'isbn' => '9780000000003',
        ]);
    }
}

