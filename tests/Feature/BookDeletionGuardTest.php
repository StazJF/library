<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Borrow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_delete_book_with_active_borrow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $book = Book::factory()->create([
            'copies' => 1,
            'available_copies' => 0,
            'status' => 'borrowed',
        ]);

        $copy = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'TST-001',
            'status' => 'borrowed',
            'condition' => 'good',
            'is_lost_damaged' => false,
        ]);

        Borrow::create([
            'user_id' => $admin->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'returned_at' => null,
            'role' => 'student',
            'origin' => 'personal',
        ]);

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.catalog'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_delete_book_without_active_borrow(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $book = Book::factory()->create([
            'copies' => 1,
            'available_copies' => 1,
            'status' => 'available',
        ]);

        BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'TST-002',
            'status' => 'available',
            'condition' => 'good',
            'is_lost_damaged' => false,
        ]);

        $response = $this->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.catalog'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('books', [
            'id' => $book->id,
        ]);
    }
}

