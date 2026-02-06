<?php

namespace Modules\Task\tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Task\app\Models\Task;
use Laravel\Sanctum\Sanctum;

class TaskRoutesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guest_cannot_access_task_routes()
    {
        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_view_tasks_index()
    {
        // Create a user and token
        $user = \App\Models\User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        // Create a task
        Task::factory()->create();

        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /** @test */
    public function lecturer_can_create_task_via_lecturer_routes()
    {
        $lecturer = \App\Models\User::factory()->create(['role' => 'lecturer']);
        Sanctum::actingAs($lecturer, ['*']);

        $payload = [
            'title' => 'Test Task',
            'description' => 'Demo',
        ];

        $response = $this->postJson('/api/v1/lecturer-tasks', $payload);
        $response->assertStatus(201);
        $this->assertDatabaseHas('tasks', ['title' => 'Test Task']);
    }
}
