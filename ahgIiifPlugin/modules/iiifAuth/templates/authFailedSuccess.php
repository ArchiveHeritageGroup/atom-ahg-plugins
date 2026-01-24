<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .auth-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 400px;
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin: 0 0 10px;
            font-size: 24px;
        }
        p {
            color: #666;
        }
        .btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="error-icon">✗</div>
        <h1><?php echo isset($service) ? esc_specialchars($service->failure_header) : 'Access Denied' ?></h1>
        <p><?php echo esc_specialchars($error ?? 'You do not have permission to access this resource.') ?></p>
        <button class="btn" onclick="window.close()">Close</button>
    </div>
</body>
</html>
