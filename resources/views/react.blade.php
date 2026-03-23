<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        // Всегда используем собранные файлы (без dev сервера)
        $indexHtmlPath = public_path('frontend/index.html');
        $assetsPath = public_path('frontend/assets');
        $cssFiles = [];
        $jsFiles = [];
        
        // В проде иногда остаются старые index-*.js/css файлы.
        // Чтобы исключить загрузку устаревших чанков, всегда выбираем самый свежий файл по времени изменения.
        if (is_dir($assetsPath)) {
            $latestFile = static function (array $files): ?string {
                if (empty($files)) {
                    return null;
                }

                usort($files, static function (string $a, string $b): int {
                    return filemtime($b) <=> filemtime($a);
                });

                return $files[0] ?? null;
            };

            $latestJs = $latestFile(glob($assetsPath . '/index-*.js') ?: []);
            $latestCss = $latestFile(glob($assetsPath . '/index-*.css') ?: []);

            if ($latestJs) {
                $jsFiles[] = '/frontend/assets/' . basename($latestJs);
            }

            if ($latestCss) {
                $cssFiles[] = '/frontend/assets/' . basename($latestCss);
            }
        }

        // Fallback: если assets пустой, пробуем взять из frontend/index.html
        if ((empty($jsFiles) || empty($cssFiles)) && file_exists($indexHtmlPath)) {
            $htmlContent = file_get_contents($indexHtmlPath);

            if (empty($cssFiles)) {
                preg_match_all('/<link[^>]*href=["\']([^"\']*\.css[^"\']*)["\'][^>]*>/i', $htmlContent, $cssMatches);
                if (!empty($cssMatches[1])) {
                    $cssFiles[] = $cssMatches[1][0];
                }
            }

            if (empty($jsFiles)) {
                preg_match_all('/<script[^>]*src=["\']([^"\']*\.js[^"\']*)["\'][^>]*>/i', $htmlContent, $jsMatches);
                if (!empty($jsMatches[1])) {
                    $jsFiles[] = $jsMatches[1][0];
                }
            }
        }
    @endphp
    
    @if(!empty($jsFiles))
        <!-- Подключение собранных файлов React -->
        {{-- Подключаем CSS файлы --}}
        @foreach($cssFiles as $css)
            @if(str_starts_with($css, 'http://') || str_starts_with($css, 'https://'))
                <link rel="stylesheet" href="{{ $css }}">
            @elseif(str_starts_with($css, '/frontend/'))
                <link rel="stylesheet" href="{{ $css }}">
            @elseif(str_starts_with($css, '/assets/'))
                <link rel="stylesheet" href="{{ $css }}">
            @elseif(str_starts_with($css, '/'))
                <link rel="stylesheet" href="{{ $css }}">
            @else
                <link rel="stylesheet" href="{{ asset($css) }}">
            @endif
        @endforeach
        
        {{-- Подключаем JS файлы --}}
        @foreach($jsFiles as $js)
            @if(str_starts_with($js, 'http://') || str_starts_with($js, 'https://'))
                <script type="module" src="{{ $js }}"></script>
            @elseif(str_starts_with($js, '/frontend/'))
                <script type="module" src="{{ $js }}"></script>
            @elseif(str_starts_with($js, '/assets/'))
                <script type="module" src="{{ $js }}"></script>
            @elseif(str_starts_with($js, '/'))
                <script type="module" src="{{ $js }}"></script>
            @else
                <script type="module" src="{{ asset($js) }}"></script>
            @endif
        @endforeach
    @else
        <!-- React приложение не собрано. Выполните сборку: npm run build:react -->
        <div style="padding: 20px; text-align: center; font-family: Arial;">
            <h2>React приложение не собрано</h2>
            <p>Выполните сборку:</p>
            <pre style="background: #f5f5f5; padding: 10px; display: inline-block;">cd frontend && npm run build</pre>
        </div>
        <script>
            console.error('React приложение не собрано. Выполните: cd frontend && npm run build');
        </script>
    @endif
    
    <!-- Telegram WebApp Script - должен загружаться до React приложения -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        // Проверка загрузки Telegram WebApp
        (function() {
            function checkTelegram() {
                if (window.Telegram && window.Telegram.WebApp) {
                    console.log('Telegram WebApp loaded successfully', {
                        version: window.Telegram.WebApp.version,
                        platform: window.Telegram.WebApp.platform,
                        hasUser: !!window.Telegram.WebApp.initDataUnsafe?.user,
                        userId: window.Telegram.WebApp.initDataUnsafe?.user?.id,
                    });
                } else {
                    console.warn('Telegram WebApp not loaded yet');
                }
            }
            
            // Проверяем сразу
            checkTelegram();
            
            // Проверяем после загрузки DOM
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', checkTelegram);
            } else {
                setTimeout(checkTelegram, 100);
            }
        })();
    </script>
</head>

<body>
    <div id="root"></div>
</body>
</html>
