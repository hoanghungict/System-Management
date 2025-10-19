<?php

namespace Modules\Task\app\Lecturer\UseCases;

use Modules\Task\app\Lecturer\Repositories\LecturerCalendarRepository;
use Modules\Task\app\Lecturer\Exceptions\LecturerTaskException;

/**
 * Lecturer Calendar Use Case
 */
class LecturerCalendarUseCase
{
    protected $lecturerCalendarRepository;

    public function __construct(LecturerCalendarRepository $lecturerCalendarRepository)
    {
        $this->lecturerCalendarRepository = $lecturerCalendarRepository;
    }

    public function getLecturerEvents($lecturerId, $filters = [])
    {
        try {
            $events = $this->lecturerCalendarRepository->getLecturerEvents($lecturerId, $filters);
            return $events;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve lecturer events: ' . $e->getMessage(), 500);
        }
    }

    public function createEvent($data)
    {
        try {
            $event = $this->lecturerCalendarRepository->createEvent($data);
            return $event;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to create event: ' . $e->getMessage(), 500);
        }
    }

    public function updateEvent($eventId, $data, $lecturerId, $userType)
    {
        try {
            $event = $this->lecturerCalendarRepository->updateEvent($eventId, $data, $lecturerId, $userType);
            return $event;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to update event: ' . $e->getMessage(), 500);
        }
    }

    public function deleteEvent($eventId, $lecturerId, $userType)
    {
        try {
            $this->lecturerCalendarRepository->deleteEvent($eventId, $lecturerId, $userType);
            return true;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to delete event: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByDate($lecturerId, $date)
    {
        try {
            $events = $this->lecturerCalendarRepository->getEventsByDate($lecturerId, $date);
            return $events;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve events by date: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsByRange($lecturerId, $startDate, $endDate)
    {
        try {
            $events = $this->lecturerCalendarRepository->getEventsByRange($lecturerId, $startDate, $endDate);
            return $events;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve events by range: ' . $e->getMessage(), 500);
        }
    }

    public function getUpcomingEvents($lecturerId, $limit = 10)
    {
        try {
            $events = $this->lecturerCalendarRepository->getUpcomingEvents($lecturerId, $limit);
            return $events;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve upcoming events: ' . $e->getMessage(), 500);
        }
    }

    public function getOverdueEvents($lecturerId)
    {
        try {
            $events = $this->lecturerCalendarRepository->getOverdueEvents($lecturerId);
            return $events;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve overdue events: ' . $e->getMessage(), 500);
        }
    }

    public function getEventsCountByStatus($lecturerId)
    {
        try {
            $counts = $this->lecturerCalendarRepository->getEventsCountByStatus($lecturerId);
            return $counts;
        } catch (\Exception $e) {
            throw new LecturerTaskException('Failed to retrieve events count by status: ' . $e->getMessage(), 500);
        }
    }
}
