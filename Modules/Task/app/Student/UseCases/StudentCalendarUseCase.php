<?php

namespace Modules\Task\app\Student\UseCases;

use Modules\Task\app\Student\Repositories\StudentCalendarRepository;
use Modules\Task\app\Student\Exceptions\StudentTaskException;

/**
 * Student Calendar Use Case
 */
class StudentCalendarUseCase
{
    protected $studentCalendarRepository;

    public function __construct(StudentCalendarRepository $studentCalendarRepository)
    {
        $this->studentCalendarRepository = $studentCalendarRepository;
    }

    public function getStudentEvents($studentId, $filters = [])
    {
        try {
            $events = $this->studentCalendarRepository->getStudentEvents($studentId, $filters);
            return $events;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByDate($studentId, $date)
    {
        try {
            $events = $this->studentCalendarRepository->getEventsByDate($studentId, $date);
            return $events;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByRange($studentId, $startDate, $endDate)
    {
        try {
            $events = $this->studentCalendarRepository->getEventsByRange($studentId, $startDate, $endDate);
            return $events;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events by range: ' . $e->getMessage(), 500);
        }
    }

    public function getUpcomingEvents($studentId, $limit = 10)
    {
        try {
            $events = $this->studentCalendarRepository->getUpcomingEvents($studentId, $limit);
            return $events;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    public function getOverdueEvents($studentId)
    {
        try {
            $events = $this->studentCalendarRepository->getOverdueEvents($studentId);
            return $events;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsCountByStatus($studentId)
    {
        try {
            $counts = $this->studentCalendarRepository->getEventsCountByStatus($studentId);
            return $counts;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve events count by status: ' . $e->getMessage(), 500);
        }
    }

    public function getReminders($studentId, $filters = [])
    {
        try {
            $reminders = $this->studentCalendarRepository->getReminders($studentId, $filters);
            return $reminders;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to retrieve student reminders: ' . $e->getMessage(), 500);
        }
    }

    public function setReminder($data)
    {
        try {
            $reminder = $this->studentCalendarRepository->setReminder($data);
            return $reminder;
        } catch (\Exception $e) {
            throw new StudentTaskException('Failed to set reminder: ' . $e->getMessage(), 500);
        }
    }
}
