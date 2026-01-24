<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_specialchars($service->label) ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .auth-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            text-align: center;
        }
        h1 {
            color: #333;
            margin: 0 0 20px;
            font-size: 24px;
        }
        .description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><?php echo esc_specialchars($service->label) ?></h1>
        <p class="description"><?php echo esc_specialchars($service->description) ?></p>
        <form action="<?php echo url_for('iiif_auth_confirm', ['service' => $service->name]) ?>" method="POST">
            <button type="submit" class="btn"><?php echo esc_specialchars($service->confirm_label ?: 'I Agree') ?></button>
        </form>
    </div>
</body>
</html>
