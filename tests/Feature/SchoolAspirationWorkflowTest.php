<?php

namespace Tests\Feature;

use App\Models\Aspiration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolAspirationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_student_can_register_and_submit_an_aspiration(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Siswa Satu',
            'email' => 'siswa@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'siswa@example.test', 'role' => 'student']);

        $this->post(route('aspirations.store'), [
            'title' => 'Kipas ruang kelas rusak',
            'category' => 'facility',
            'description' => 'Kipas di kelas 8A sudah tidak berputar sejak minggu lalu.',
        ])->assertRedirect(route('dashboard'));

        $aspiration = Aspiration::with('updates')->firstOrFail();
        $this->assertSame('submitted', $aspiration->status);
        $this->assertCount(1, $aspiration->updates);
    }

    public function test_admin_can_filter_aspirations_and_publish_progress_and_feedback(): void
    {
        $student = User::factory()->create(['name' => 'Siswa Target', 'role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $aspiration = Aspiration::create([
            'user_id' => $student->id,
            'title' => 'Perbaikan perpustakaan',
            'category' => 'facility',
            'description' => 'Mohon perbaiki lampu yang mati di perpustakaan.',
            'status' => 'submitted',
        ]);

        $this->actingAs($admin)->get(route('dashboard', [
            'month' => now()->format('Y-m'),
            'student_id' => $student->id,
            'category' => 'facility',
        ]))->assertOk()->assertSee('Perbaikan perpustakaan')->assertSee('Siswa Target');

        $this->actingAs($admin)->patch(route('aspirations.update', $aspiration), [
            'status' => 'in_progress',
            'progress' => 'Petugas sarana sedang memeriksa lampu.',
            'feedback' => 'Terima kasih sudah melapor.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('aspirations', ['id' => $aspiration->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('aspiration_updates', [
            'aspiration_id' => $aspiration->id,
            'progress' => 'Petugas sarana sedang memeriksa lampu.',
            'feedback' => 'Terima kasih sudah melapor.',
        ]);
    }

    public function test_students_can_only_see_their_own_aspirations(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        Aspiration::create([
            'user_id' => $otherStudent->id,
            'title' => 'Laporan milik siswa lain',
            'category' => 'other',
            'description' => 'Detail laporan yang hanya boleh dilihat pemiliknya.',
            'status' => 'submitted',
        ]);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertOk()->assertDontSee('Laporan milik siswa lain');
        $this->actingAs($owner)->patch(route('aspirations.update', Aspiration::first()), [
            'status' => 'resolved',
        ])->assertForbidden();
    }
}
