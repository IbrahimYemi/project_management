<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You're Invited!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            background: #ffffff;
            padding: 20px;
            margin: 0 auto;
            border-radius: 5px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .button {
            display: inline-block;
            background: #3498db;
            color: #ffffff;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 20px;
        }

        .button:hover {
            background: #2980b9;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Hello {{ $name }},</h2>
        <p>You have been invited to join our platform!</p>
        <p>Click the button below to accept the invitation and complete your registration.</p>
        <a href="{{ $inviteUrl }}" class="button">Accept Invitation</a>
        <p>If you did not request this invitation, you can ignore this email.</p>
        <p class="footer">This invitation will expire in 24 hours.</p>
    </div>
</body>

</html>
