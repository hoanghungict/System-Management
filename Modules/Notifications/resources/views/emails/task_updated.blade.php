<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Task Updated</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2196F3; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .task-info { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
        .changes { background: #fff3cd; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 10px 20px; background: #2196F3; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📝 Task Updated</h1>
        </div>
        
        <div class="content">
            <p>Hello {{ $receiver_name }},</p>
            
            <p>The following task has been updated:</p>
            
            <div class="task-info">
                <h3>{{ $task_title }}</h3>
                <p><strong>Updated by:</strong> {{ $updater_name }}</p>
                <p><strong>Updated at:</strong> {{ $updated_at }}</p>
            </div>
            
            <div class="changes">
                <h4>Changes Made:</h4>
                <p>{{ $change_summary }}</p>
            </div>
            
            <p>Please review the updated task details and adjust your work accordingly.</p>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ $task_url }}" class="btn">View Updated Task</a>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated notification from the Task Management System.</p>
            <p>If you have any questions, please contact your lecturer or administrator.</p>
        </div>
    </div>
</body>
</html>
