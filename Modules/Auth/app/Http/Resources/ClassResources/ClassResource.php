<?php

namespace Modules\Auth\app\Http\Resources\ClassResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'class_name' => $this->class_name,
            'class_code' => $this->class_code,
            'faculty_id' => $this->faculty_id,
            'lecturer_id' => $this->lecturer_id,
            'school_year' => $this->school_year,
            'faculty' => $this->whenLoaded('faculty', function () {
                return [
                    'id' => $this->faculty->id,
                    'name' => $this->faculty->name,
                    'type' => $this->faculty->type
                ];
            }),
            'lecturer' => $this->whenLoaded('lecturer', function () {
                return [
                    'id' => $this->lecturer->id,
                    'full_name' => $this->lecturer->full_name,
                    'lecturer_code' => $this->lecturer->lecturer_code
                ];
            }),
            'students_count' => $this->whenLoaded('students', function () {
                return $this->students->count();
            }),
            'students' => $this->whenLoaded('students', function () {
                return $this->students->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'full_name' => $student->full_name,
                        'student_code' => $student->student_code
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
