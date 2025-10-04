<?php

namespace Modules\Task\app\Student\Repositories;

use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Calendar Repository
 */
class StudentCalendarRepository
{
    public function getStudentEvents($studentId, $filters = [])
    {
        try {
            return [
                'data' => [
                    ['id' => 1, 'title' => 'Event 1', 'start' => '2024-09-10 08:00', 'end' => '2024-09-10 10:00'],
                    ['id' => 2, 'title' => 'Event 2', 'start' => '2024-09-11 14:00', 'end' => '2024-09-11 16:00']
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 2,
                    'last_page' => 1
                ]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByDate($studentId, $date)
    {
        try {
            return [
                ['id' => 1, 'title' => 'Event 1', 'start' => $date . ' 08:00', 'end' => $date . ' 10:00'],
                ['id' => 2, 'title' => 'Event 2', 'start' => $date . ' 14:00', 'end' => $date . ' 16:00']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByRange($studentId, $startDate, $endDate)
    {
        try {
            return [
                ['id' => 1, 'title' => 'Event 1', 'start' => $startDate . ' 08:00', 'end' => $startDate . ' 10:00'],
                ['id' => 2, 'title' => 'Event 2', 'start' => $endDate . ' 14:00', 'end' => $endDate . ' 16:00']
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events by range: ' . $e->getMessage(), 500);
        }
    }

    public function getUpcomingEvents($studentId, $limit = 10)
    {
        try {
            return [
                ['id' => 1, 'title' => 'Upcoming Event 1', 'start' => now()->addDays(1)->format('Y-m-d H:i:s')],
                ['id' => 2, 'title' => 'Upcoming Event 2', 'start' => now()->addDays(2)->format('Y-m-d H:i:s')]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    public function getOverdueEvents($studentId)
    {
        try {
            return [
                ['id' => 1, 'title' => 'Overdue Event 1', 'start' => now()->subDays(1)->format('Y-m-d H:i:s')],
                ['id' => 2, 'title' => 'Overdue Event 2', 'start' => now()->subDays(2)->format('Y-m-d H:i:s')]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsCountByStatus($studentId)
    {
        try {
            return [
                'total' => 10,
                'pending' => 5,
                'completed' => 3,
                'overdue' => 2
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events count by status: ' . $e->getMessage(), 500);
        }
    }

    public function getReminders($studentId, $filters = [])
    {
        try {
            return [
                'data' => [
                    ['id' => 1, 'title' => 'Reminder 1', 'remind_at' => now()->addHours(1)],
                    ['id' => 2, 'title' => 'Reminder 2', 'remind_at' => now()->addHours(2)]
                ],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 15,
                    'total' => 2,
                    'last_page' => 1
                ]
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student reminders: ' . $e->getMessage(), 500);
        }
    }

    public function setReminder($data)
    {
        try {
            return [
                'id' => 1,
                'title' => $data['title'],
                'remind_at' => $data['remind_at'],
                'user_id' => $data['user_id'],
                'user_type' => $data['user_type'],
                'created_at' => now()
            ];
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to set reminder: ' . $e->getMessage(), 500);
        }
    }
}
