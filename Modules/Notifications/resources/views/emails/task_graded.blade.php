<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Graded</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .task-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .grade-info { background: #e8f5e8; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .grade-excellent { background: #e8f5e8; border-left-color: #4CAF50; }
        .grade-good { background: #fff3e0; border-left-color: #FF9800; }
        .grade-poor { background: #ffebee; border-left-color: #f44336; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
        .grade-display { font-size: 24px; font-weight: bold; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Task Graded</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $student_name }},</p>
            
            <p>Your task has been graded:</p>
            
            <div class="task-info">
                <h3>{{ $task_title }}</h3>
                <p><strong>Graded by:</strong> {{ $grader_name }}</p>
                <p><strong>Graded at:</strong> {{ $graded_at }}</p>
            </div>
            
            <div class="grade-info grade-{{ $grade_percentage >= 80 ? 'excellent' : ($grade_percentage >= 60 ? 'good' : 'poor') }}">
                <div class="grade-display">
                    {{ $grade_emoji }} {{ $grade }}/{{ $max_grade }} ({{ $grade_percentage }}%)
                </div>
                <p><strong>Grade Status:</strong> {{ $grade_status }}</p>
                @if($feedback)
                    <p><strong>Feedback:</strong> {{ $feedback }}</p>
                @endif
            </div>
            
            @if($is_pass)
                <div style="background: #e8f5e8; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50;">
                    <h4>✅ Congratulations!</h4>
                    <p>You have successfully completed this task.</p>
                </div>
            @else
                <div style="background: #ffebee; padding: 15px; margin: 15px 0; border-left: 4px solid #f44336;">
                    <h4>❌ Task Not Passed</h4>
                    <p>Please review the feedback and consider resubmitting if allowed.</p>
                </div>
            @endif
            
            <p>Please review your grade and feedback carefully.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $task_url }}" class="btn">View Task</a>
                <a href="{{ $grade_url }}" class="btn" style="background: #2196F3; margin-left: 10px;">View Grade Details</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the Task Management System.</p>
            <p>If you have any questions about your grade, please contact your lecturer.</p>
        </div>
    </div>
</body>
</html>
