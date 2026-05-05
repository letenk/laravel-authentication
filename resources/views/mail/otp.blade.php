<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    <p>Hi {{ $name }},</p>
    <p>Your verification code is:</p>
    <h2>{{ $code }}</h2>
    <p>This code will expire in {{ $expiresInMinutes }} minutes.</p>
    <p>If you did not request this, please ignore this email.</p>
</body>
</html>
