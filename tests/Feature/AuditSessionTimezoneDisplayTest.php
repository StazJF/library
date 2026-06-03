<?php

namespace Tests\Feature;

use App\Models\AuditSession;
use App\Models\SystemUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditSessionTimezoneDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_summary_displays_started_at_in_display_timezone(): void
    {
        $admin = SystemUser::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'employee_id' => 'EMP-001',
        ]);

        $this->actingAs($admin);

        Carbon::setTestNow(Carbon::create(2026, 4, 26, 0, 15, 0, 'UTC'));

        $this->post(route('audit.store'), [
            'school_year' => '2025-2026',
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ])->assertRedirect();

        $session = AuditSession::latest('id')->firstOrFail();

        $expected = Carbon::now('UTC')
            ->timezone(config('app.display_timezone'))
            ->format('M d, Y h:i A');

        $this->get(route('audit.summary', $session))
            ->assertOk()
            ->assertSee($expected);
    }
}

