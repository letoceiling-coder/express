<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Позвонить</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }
        
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        
        .phone-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .phone-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 30px;
            word-break: break-all;
        }
        
        .call-button {
            display: inline-block;
            width: 100%;
            padding: 18px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .call-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .call-button:active {
            transform: translateY(0);
        }
        
        .hint {
            font-size: 12px;
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📞</div>
        <div class="phone-label">Номер телефона</div>
        <div class="phone-number" id="phoneNumber">{{ $phone ?? 'Не указан' }}</div>
        <a href="tel:{{ $phone ?? '' }}" class="call-button" id="callButton">
            Позвонить
        </a>
        <div class="hint">Нажмите кнопку "Позвонить" для вызова</div>
    </div>
    
    <script>
        // Форматируем номер телефона для отображения
        (function() {
            const phoneElement = document.getElementById('phoneNumber');
            const phone = phoneElement.textContent.trim();
            
            if (phone && phone !== 'Не указан') {
                // Убираем все нецифровые символы кроме +
                const digits = phone.replace(/[^\d+]/g, '');
                
                // Форматируем: +7 (XXX) XXX-XX-XX
                if (digits.length === 12 && digits.startsWith('+7')) {
                    const formatted = `+7 (${digits.slice(2, 5)}) ${digits.slice(5, 8)}-${digits.slice(8, 10)}-${digits.slice(10)}`;
                    phoneElement.textContent = formatted;
                } else if (digits.length === 11 && digits.startsWith('7')) {
                    const formatted = `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9)}`;
                    phoneElement.textContent = formatted;
                }
            }
        })();
    </script>
</body>
</html>

