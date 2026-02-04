<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Not Found</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 16px;
            padding: 48px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .error-icon svg {
            width: 40px;
            height: 40px;
            color: #f59e0b;
        }
        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .tenant-code {
            display: inline-block;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 16px;
            color: #4b5563;
            margin: 8px 0;
        }
        p {
            color: #6b7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .help-text {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h1>Tenant Not Found</h1>
        <div class="tenant-code"><?php echo $tenantCode ?? 'unknown'; ?></div>
        <p>The tenant you're looking for doesn't exist or may have been removed. Please check the URL and try again.</p>
        <div class="actions">
            <a href="/" class="btn btn-primary">Go to Main Site</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
        <div class="help-text">
            If you believe this is an error, please contact the administrator.
        </div>
    </div>
</body>
</html>
