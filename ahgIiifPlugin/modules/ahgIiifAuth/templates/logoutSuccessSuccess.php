<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out</title>
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
        h1 {
            color: #333;
            margin: 0 0 10px;
            font-size: 24px;
        }
        p {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>Logged Out</h1>
        <p>Your IIIF access token has been revoked.</p>
        <p>You may close this window.</p>
    </div>
    <script>
        if (window.opener) {
            window.opener.postMessage({type: 'iiif-auth-logout'}, '*');
        }
        setTimeout(function() {
            window.close();
        }, 2000);
    </script>
</body>
</html>
