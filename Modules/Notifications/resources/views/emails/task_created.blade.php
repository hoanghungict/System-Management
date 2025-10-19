<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Task Assignment</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .task-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
        .priority-high { border-left-color: #f44336; }
        .priority-medium { border-left-color: #ff9800; }
        .priority-low { border-left-color: #4CAF50; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 New Task Assignment</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $receiver_name }},</p>
            
            <p>You have been assigned a new task:</p>
            
            <div class="task-info priority-{{ $priority }}">
                <h3>{{ $task_title }}</h3>
                <p><strong>Description:</strong> {{ $task_description }}</p>
                <p><strong>Priority:</strong> {{ $priority }}</p>
                <p><strong>Deadline:</strong> {{ $deadline }}</p>
                <p><strong>Created by:</strong> {{ $creator_name }}</p>
                <p><strong>Created at:</strong> {{ $created_at }}</p>
            </div>
            
            <p>Please review the task details and start working on it as soon as possible.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $task_url }}" class="btn">View Task</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the Task Management System.</p>
            <p>If you have any questions, please contact your lecturer or administrator.</p>
        </div>
    </div>
</body>
</html>
