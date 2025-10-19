<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Submission Received</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .task-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #FF9800; }
        .submission-info { background: #e8f5e8; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .late-submission { background: #ffebee; padding: 15px; margin: 15px 0; border-left: 4px solid #f44336; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #FF9800; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📤 Task Submission Received</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $creator_name }},</p>
            
            <p>A student has submitted their work for the following task:</p>
            
            <div class="task-info">
                <h3>{{ $task_title }}</h3>
                <p><strong>Submitted by:</strong> {{ $submitter_name }}</p>
                <p><strong>Submitted at:</strong> {{ $submitted_at }}</p>
            </div>
            
            <div class="submission-info">
                <h4>Submission Details:</h4>
                <p><strong>Content:</strong> {{ $submission_content }}</p>
            </div>
            
            @if($is_late)
                <div class="late-submission">
                    <h4>⚠️ Late Submission</h4>
                    <p>This submission is {{ $days_late }} days late. Please review accordingly.</p>
                </div>
            @endif
            
            <p>Please review the submission and provide feedback or grading as appropriate.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $task_url }}" class="btn">View Task</a>
                <a href="{{ $submission_url }}" class="btn" style="background: #4CAF50; margin-left: 10px;">Review Submission</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the Task Management System.</p>
            <p>If you have any questions, please contact the system administrator.</p>
        </div>
    </div>
</body>
</html>
