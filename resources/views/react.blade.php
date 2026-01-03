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
        
        // Пытаемся найти файлы через index.html или через поиск в assets
        if (file_exists($indexHtmlPath)) {
            $htmlContent = file_get_contents($indexHtmlPath);
            
            // Извлекаем пути к CSS файлам
            preg_match_all('/<link[^>]*href=["\']([^"\']*\.css[^"\']*)["\'][^>]*>/i', $htmlContent, $cssMatches);
            if (!empty($cssMatches[1])) {
                foreach ($cssMatches[1] as $cssPath) {
                    $cssFiles[] = $cssPath;
                }
            }
            
            // Извлекаем пути к JS файлам
            preg_match_all('/<script[^>]*src=["\']([^"\']*\.js[^"\']*)["\'][^>]*>/i', $htmlContent, $jsMatches);
            if (!empty($jsMatches[1])) {
                foreach ($jsMatches[1] as $jsPath) {
                    $jsFiles[] = $jsPath;
                }
            }
        }
        
        // Если не нашли через index.html, ищем файлы по паттерну
        if (empty($jsFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.js');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $jsFiles[] = 'frontend/assets/' . basename($file);
                }
            }
        }
        
        if (empty($cssFiles) && is_dir($assetsPath)) {
            $files = glob($assetsPath . '/index-*.css');
            if (!empty($files)) {
                foreach ($files as $file) {
                    $cssFiles[] = 'frontend/assets/' . basename($file);
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
</head>

<body>
    <div id="root"></div>
</body>
</html>
