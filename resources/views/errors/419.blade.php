<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>کاتی پەڕەکە بەسەرچوو - MarketLink</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Vazirmatn', system-ui, sans-serif; }
        body {
            background: #0b0f19;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }
        .icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.6rem;
        }
        p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1.8rem;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: #4f46e5;
            color: #fff;
            padding: 0.8rem 1.6rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        .btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⏳</div>
        <h1>کاتی پەڕەکە بەسەرچووە (419)</h1>
        <p>
            بۆ پاراستنی ئاسایشی زانیارییەکانت، کاتی بەکارهێنانی ئەم پەڕەیە کۆتایی هاتبوو (CSRF Session Expired).
            تکایە کلیک لەسەر دوگمەی خوارەوە بکە بۆ نوێکردنەوە و تەواوکردنی کارەکەت.
        </p>
        <button type="button" class="btn" onclick="window.location.href = '/'">
            <span>🔄</span> نوێکردنەوە و گەڕانەوە بۆ سیستەم
        </button>
    </div>
</body>
</html>
