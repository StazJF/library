<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrow;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUpcomingDueDatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_personal_borrow_shows_in_upcoming_due_dates_modal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-13 10:00:00'));

        $authUser = User::factory()->create();
        $this->actingAs($authUser);

        $teacher = Teacher::create([
            'id' => 999,
            'name' => 'Jane Teacher',
            'email' => 'jane.teacher@example.com',
        ]);

        $book = Book::factory()->create();

        // Teacher personal borrow due soon.
        Borrow::create([
            'user_id' => $teacher->id,
            'book_id' => $book->id,
            'borrowed_at' => Carbon::today(),
            'due_date' => Carbon::today()->addDays(2),
            'returned_at' => null,
            'role' => 'teacher',
            'origin' => 'personal',
        ]);

        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Teachers (1)');
        $response->assertSee('Jane Teacher');
        $response->assertSee($book->title);
    }
}
