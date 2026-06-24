<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — Notifyr</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Instrument Sans', system-ui, sans-serif; background: #f9fafb; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
        .container { text-align: center; max-width: 320px; }
        .icon { width: 72px; height: 72px; background: linear-gradient(135deg, #6366f1, #8b5cf6); border-radius: 24px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
        .icon svg { width: 36px; height: 36px; color: white; }
        h1 { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
        p { font-size: 0.9rem; color: #6b7280; line-height: 1.6; margin-bottom: 1.5rem; }
        button { background: linear-gradient(135deg, #4f46e5, #6366f1); color: white; font-weight: 700; font-size: 0.875rem; border: none; border-radius: 12px; padding: 0.75rem 1.5rem; cursor: pointer; }
        button:active { transform: scale(0.98); }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636a9 9 0 010 12.728M5.636 5.636a9 9 0 000 12.728M12 12h.01M15.536 8.464a5 5 0 010 7.072M8.464 8.464a5 5 0 000 7.072"/>
            </svg>
        </div>
        <h1>You're offline</h1>
        <p>Please check your connection and try again. Your data will sync automatically when you're back online.</p>
        <button onclick="location.reload()">Try again</button>
    </div>
</body>
</html>
