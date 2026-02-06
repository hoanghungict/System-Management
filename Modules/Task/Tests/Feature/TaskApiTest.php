<?php

namespace Modules\Task\Tests\Feature;

use Tests\TestCase;
use Modules\Task\app\Models\Task;
use Modules\Task\app\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable the Task module
        $this->artisan('module:enable', ['module' => 'Task']);
    }

    #[Test]
    public function it_can_list_tasks()
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_create_task()
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'receiver_id' => 1,
            'receiver_type' => 'lecturer',
            'creator_id' => 1,
            'creator_type' => 'lecturer'
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'receiver_id',
                        'receiver_type',
                        'creator_id',
                        'creator_type',
                        'created_at'
                    ],
                    'message'
                ]);
    }

    #[Test]
    public function it_can_show_task()
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_update_task()
    {
        $task = Task::factory()->create();
        $updateData = [
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'receiver_id' => 1,
            'receiver_type' => 'lecturer',
            'creator_id' => 1,
            'creator_type' => 'lecturer'
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_delete_task()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_get_tasks_by_receiver()
    {
        $response = $this->getJson('/api/tasks/by-receiver?receiver_id=1&receiver_type=lecturer');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_get_tasks_by_creator()
    {
        $response = $this->getJson('/api/tasks/by-creator?creator_id=1&creator_type=lecturer');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }

    #[Test]
    public function it_can_get_task_statistics()
    {
        $response = $this->getJson('/api/tasks/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);
    }
}
