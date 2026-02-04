<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Not Configured</title>
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
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .error-icon svg {
            width: 40px;
            height: 40px;
            color: #ef4444;
        }
        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .domain-name {
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
        .help-text ul {
            list-style: none;
            margin-top: 12px;
        }
        .help-text li {
            padding: 4px 0;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
            </svg>
        </div>
        <h1>Domain Not Configured</h1>
        <div class="domain-name"><?php echo $domainName ?? 'unknown'; ?></div>
        <p>This domain is not configured for any tenant in our system. If you own this domain and want to use it with our platform, please contact the administrator.</p>
        <div class="actions">
            <a href="/" class="btn btn-primary">Go to Main Site</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
        <div class="help-text">
            <strong>For administrators:</strong>
            <ul>
                <li>1. Go to Admin &gt; Tenants</li>
                <li>2. Edit the target tenant</li>
                <li>3. Set the custom domain field</li>
                <li>4. Configure DNS and SSL</li>
            </ul>
        </div>
    </div>
</body>
</html>
