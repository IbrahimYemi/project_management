<!DOCTYPE html>
<html>

<head>
    <title>Task Notification</title>
</head>

<body>
    <h1>Task Notification</h1>

    <p>{{ $taskMessage }}</p>

    <ul>
        <li><strong>Title:</strong> {{ $task['title'] ?? 'N/A' }}</li>
        <li><strong>Description:</strong> {!! $task['description'] ?? 'No description provided' !!}</li>
        <li><strong>Due Date:</strong>
            {{ \Carbon\Carbon::parse($task['due_date'])->toFormattedDateString() ?? 'Not set' }}</li>
        <li><strong>Priority:</strong> {{ $task['priority'] ?? 'N/A' }}</li>
    </ul>

    <p>
        <a href="{{ $url }}"
            style="padding: 10px 15px; background-color: #3490dc; color: white; text-decoration: none; border-radius: 5px;">
            View Task
        </a>
    </p>

    <p>Thanks,<br>{{ config('app.name') }}</p>
</body>

</html>
