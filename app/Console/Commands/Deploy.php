<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class Deploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy
                            {--message= : Кастомное сообщение для коммита}
                            {--skip-build : Пропустить npm run build}
                            {--dry-run : Показать что будет сделано без выполнения}
                            {--insecure : Отключить проверку SSL сертификата (для разработки)}
                            {--with-seed : Выполнить seeders на сервере (по умолчанию пропускаются)}
                            {--force : Принудительная отправка (force push) - перезаписывает удаленную ветку}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Деплой проекта: сборка, коммит в git, отправка на сервер';

    /**
     * Git repository URL
     *
     * @var string
     */
    protected $gitRepository = 'https://github.com/letoceiling-coder/express.git';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Начало процесса деплоя...');
        $this->newLine();

        $dryRun = $this->option('dry-run');

        try {
            // Шаг 1: Сборка фронтенда
            if (!$this->option('skip-build')) {
                $this->buildFrontend($dryRun);
            } else {
                $this->warn('⚠️  Пропущена сборка фронтенда (--skip-build)');
            }

            // Шаг 2: Проверка git статуса
            $hasChanges = $this->checkGitStatus($dryRun);

            if (!$hasChanges && !$dryRun) {
                $this->warn('⚠️  Нет изменений для коммита.');
                // В неинтерактивном режиме автоматически продолжаем
                if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                    if (!$this->confirm('Продолжить деплой без изменений?', false)) {
                        $this->info('Деплой отменен.');
                        return 0;
                    }
                } else {
                    $this->info('  ℹ️  Продолжаем деплой без изменений (неинтерактивный режим)');
                }
            }

            // Шаг 3: Проверка remote репозитория
            $this->ensureGitRemote($dryRun);

            // Шаг 3.5: Проверка актуальности коммитов
            $this->checkCommitsUpToDate($dryRun);

            // Шаг 4: Добавление изменений в git
            if ($hasChanges) {
                $this->addChangesToGit($dryRun);

                // Шаг 4.5: Обновление версии приложения для сброса кеша Telegram miniApp
                if (!$dryRun) {
                    $this->updateAppVersion();
                }

                // Шаг 5: Создание коммита
                $commitMessage = $this->createCommit($dryRun);

                // Шаг 6: Отправка в репозиторий
                $this->pushToRepository($dryRun);
            }

            // Шаг 7: Отправка POST запроса на сервер
            if (!$dryRun) {
                $this->sendDeployRequest();
            } else {
                $this->info('📤 [DRY-RUN] Отправка POST запроса на сервер пропущена');
            }

            $this->newLine();
            $this->info('✅ Деплой успешно завершен!');
            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка деплоя: ' . $e->getMessage());
            if ($this->option('verbose')) {
                $this->error($e->getTraceAsString());
            }
            return 1;
        }
    }

    /**
     * Сборка фронтенда
     */
    protected function buildFrontend(bool $dryRun): void
    {
        $this->info('📦 Шаг 1: Сборка фронтенда...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: npm run build:all');
            return;
        }

        // Увеличиваем таймаут до 5 минут (300 секунд) для сборки фронтенда
        $process = Process::timeout(300)->run('npm run build:all');

        if (!$process->successful()) {
            throw new \Exception("Ошибка сборки фронтенда:\n" . $process->errorOutput());
        }

        // Проверяем наличие собранных файлов
        // Vue админка - обязательна
        $buildDir = public_path('build');
        if (!File::exists($buildDir)) {
            throw new \Exception("Директория {$buildDir} (Vue админка) не найдена после сборки");
        }

        // React приложение - проверяем только если директория существует и не пустая
        $frontendDir = public_path('frontend');
        if (File::exists($frontendDir)) {
            $frontendFiles = File::allFiles($frontendDir);
            if (empty($frontendFiles)) {
                $this->line('  ℹ️  Директория public/frontend пустая, пропускаем проверку для React приложения');
            } else {
                $this->line('  ✅ React приложение собрано в public/frontend');
            }
        } else {
            $this->line('  ℹ️  Директория public/frontend не существует, пропускаем проверку для React приложения');
        }

        $this->info('  ✅ Сборка завершена успешно');
        $this->newLine();
    }

    /**
     * Проверка git статуса
     */
    protected function checkGitStatus(bool $dryRun): bool
    {
        $this->info('📋 Шаг 2: Проверка статуса git...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git status');
            return true;
        }

        $process = Process::run('git status --porcelain');

        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git статуса:\n" . $process->errorOutput());
        }

        $output = trim($process->output());
        $hasChanges = !empty($output);

        if ($hasChanges) {
            $this->line('  📝 Найдены изменения:');
            $this->line($output);

            // Проверяем на большие файлы
            $files = explode("\n", $output);
            $largeFiles = [];
            foreach ($files as $file) {
                $file = trim($file);
                if (empty($file)) continue;

                // Извлекаем имя файла (убираем статус M, A, ?? и т.д.)
                $fileName = preg_replace('/^[MADRC\?\s!]+/', '', $file);
                $fileName = trim($fileName);

                // Проверяем расширения больших файлов
                if (preg_match('/\.(rar|zip|7z|tar\.gz|tar)$/i', $fileName)) {
                    $largeFiles[] = $fileName;
                } elseif (file_exists($fileName)) {
                    $size = filesize($fileName);
                    // Предупреждаем о файлах больше 10MB
                    if ($size > 10 * 1024 * 1024) {
                        $sizeMB = round($size / 1024 / 1024, 2);
                        $largeFiles[] = "{$fileName} ({$sizeMB} MB)";
                    }
                }
            }

            if (!empty($largeFiles)) {
                $this->newLine();
                $this->warn('  ⚠️  Обнаружены большие файлы:');
                foreach ($largeFiles as $file) {
                    $this->warn("     - {$file}");
                }
                $this->warn('  💡 Рекомендуется добавить их в .gitignore перед коммитом');
                // В неинтерактивном режиме пропускаем подтверждение
                if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                    if (!$this->confirm('  Продолжить с этими файлами?', false)) {
                        throw new \Exception('Операция отменена. Добавьте большие файлы в .gitignore.');
                    }
                } else {
                    $this->info('  ℹ️  Продолжаем с большими файлами (неинтерактивный режим)');
                }
            }
        } else {
            $this->line('  ℹ️  Изменений не обнаружено');
        }

        $this->newLine();
        return $hasChanges;
    }

    /**
     * Проверка и настройка git remote
     */
    protected function ensureGitRemote(bool $dryRun): void
    {
        $this->info('🔗 Шаг 3: Проверка git remote...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git remote -v');
            return;
        }

        $process = Process::run('git remote -v');

        if (!$process->successful()) {
            throw new \Exception("Ошибка проверки git remote:\n" . $process->errorOutput());
        }

        $output = trim($process->output());

        // Проверяем, существует ли origin с правильным URL
        if (empty($output)) {
            $this->line('  ➕ Добавление origin remote...');
            $process = Process::run("git remote add origin {$this->gitRepository}");

            if (!$process->successful()) {
                throw new \Exception("Ошибка добавления remote:\n" . $process->errorOutput());
            }

            $this->info('  ✅ Remote origin добавлен');
        } else {
            // Проверяем, правильный ли URL у origin
            if (!str_contains($output, $this->gitRepository)) {
                $this->line('  🔄 Обновление origin remote...');
                $process = Process::run("git remote set-url origin {$this->gitRepository}");

                if (!$process->successful()) {
                    throw new \Exception("Ошибка обновления remote:\n" . $process->errorOutput());
                }

                $this->info('  ✅ Remote origin обновлен');
            } else {
                $this->line('  ✅ Remote origin настроен правильно');
            }
        }

        $this->newLine();
    }

    /**
     * Проверка актуальности коммитов
     */
    protected function checkCommitsUpToDate(bool $dryRun): void
    {
        $this->info('🔍 Шаг 3.5: Проверка актуальности коммитов...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: проверка коммитов');
            return;
        }

        try {
            // Получаем текущую ветку
            $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
            $currentBranch = trim($branchProcess->output()) ?: 'main';

            // Получаем локальный коммит
            $localCommitProcess = Process::run('git rev-parse HEAD');
            $localCommit = trim($localCommitProcess->output());

            if (empty($localCommit)) {
                $this->warn('  ⚠️  Не удалось определить локальный коммит');
                $this->newLine();
                return;
            }

            // Обновляем информацию о remote (fetch)
            $this->line('  📥 Обновление информации о remote...');
            $fetchProcess = Process::run("git fetch origin {$currentBranch} 2>&1");

            if (!$fetchProcess->successful()) {
                $this->warn('  ⚠️  Не удалось обновить информацию о remote (возможно, ветка еще не существует на remote)');
                $this->newLine();
                return;
            }

            // Получаем удаленный коммит
            $remoteCommitProcess = Process::run("git rev-parse origin/{$currentBranch} 2>&1");
            $remoteCommit = trim($remoteCommitProcess->output());

            if (empty($remoteCommit)) {
                $this->line('  ℹ️  Удаленная ветка не найдена (первый деплой?)');
                $this->newLine();
                return;
            }

            // Сравниваем коммиты
            $localShort = substr($localCommit, 0, 7);
            $remoteShort = substr($remoteCommit, 0, 7);

            $this->line("  📍 Локальный коммит:  {$localShort}");
            $this->line("  📍 Удаленный коммит: {$remoteShort}");

            if ($localCommit === $remoteCommit) {
                $this->newLine();
                $this->warn('  ⚠️  Локальный и удаленный коммиты совпадают!');
                $this->warn('  ⚠️  На сервере уже установлена эта версия.');

                // Проверяем, есть ли локальные изменения
                $statusProcess = Process::run('git status --porcelain');
                $hasLocalChanges = !empty(trim($statusProcess->output()));

                if (!$hasLocalChanges) {
                    $this->warn('  ⚠️  Нет локальных изменений для отправки.');
                    $this->newLine();

                    if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                        if (!$this->confirm('  Продолжить деплой? (сервер уже на этой версии)', false)) {
                            $this->info('  Деплой отменен.');
                            throw new \Exception('Деплой отменен пользователем');
                        }
                    } else {
                        $this->info('  ℹ️  Продолжаем деплой (неинтерактивный режим)');
                    }
                } else {
                    $this->info('  ℹ️  Есть локальные изменения, которые будут отправлены');
                }
            } else {
                // Проверяем, отстает ли локальная ветка
                $behindProcess = Process::run("git rev-list --count HEAD..origin/{$currentBranch}");
                $behindCount = (int) trim($behindProcess->output());

                if ($behindCount > 0) {
                    $this->newLine();
                    $this->warn("  ⚠️  Локальная ветка отстает от удаленной на {$behindCount} коммит(ов)!");
                    $this->warn('  ⚠️  Рекомендуется выполнить: git pull перед деплоем');
                    $this->newLine();

                    if (php_sapi_name() === 'cli' && !$this->option('no-interaction')) {
                        if (!$this->confirm('  Продолжить деплой? (может привести к конфликтам)', false)) {
                            $this->info('  Деплой отменен.');
                            throw new \Exception('Деплой отменен пользователем');
                        }
                    } else {
                        $this->info('  ℹ️  Продолжаем деплой (неинтерактивный режим)');
                    }
                } else {
                    // Локальная ветка впереди
                    $aheadProcess = Process::run("git rev-list --count origin/{$currentBranch}..HEAD");
                    $aheadCount = (int) trim($aheadProcess->output());

                    if ($aheadCount > 0) {
                        $this->line("  ✅ Локальная ветка впереди на {$aheadCount} коммит(ов)");
                    }
                }
            }

            $this->newLine();
        } catch (\Exception $e) {
            // Если ошибка не критична (например, отмена пользователем), пробрасываем дальше
            if (str_contains($e->getMessage(), 'отменен')) {
                throw $e;
            }

            // Для других ошибок просто предупреждаем и продолжаем
            $this->warn('  ⚠️  Не удалось проверить коммиты: ' . $e->getMessage());
            $this->line('  ℹ️  Продолжаем деплой...');
            $this->newLine();
        }
    }

    /**
     * Добавление изменений в git
     */
    protected function addChangesToGit(bool $dryRun): void
    {
        $this->info('➕ Шаг 4: Добавление изменений в git...');

        if ($dryRun) {
            $this->line('  [DRY-RUN] Выполнение: git add .');
            return;
        }

        // Сначала принудительно добавляем собранные файлы (на случай если они были в .gitignore)
        if (File::exists(public_path('build'))) {
            $process = Process::run('git add -f public/build');
            if (!$process->successful()) {
                $this->warn('  ⚠️  Предупреждение: не удалось добавить public/build (Vue)');
            } else {
                $this->line('  ✅ Добавлен public/build (Vue админка)');
            }
        }

        if (File::exists(public_path('frontend'))) {
            $process = Process::run('git add -f public/frontend');
            if (!$process->successful()) {
                $this->warn('  ⚠️  Предупреждение: не удалось добавить public/frontend (React)');
            } else {
                $this->line('  ✅ Добавлен public/frontend (React приложение)');
            }
        }

        // Затем добавляем все остальные изменения
        $process = Process::run('git add .');

        if (!$process->successful()) {
            throw new \Exception("Ошибка добавления файлов в git:\n" . $process->errorOutput());
        }

        $this->info('  ✅ Файлы добавлены в git');
        $this->newLine();
    }

    /**
     * Создание коммита
     */
    protected function createCommit(bool $dryRun): string
    {
        $this->info('💾 Шаг 5: Создание коммита...');

        $customMessage = $this->option('message');
        $commitMessage = $customMessage ?: 'Deploy: ' . now()->format('Y-m-d H:i:s');

        if ($dryRun) {
            $this->line("  [DRY-RUN] Выполнение: git commit -m \"{$commitMessage}\"");
            return $commitMessage;
        }

        $process = Process::run(['git', 'commit', '-m', $commitMessage]);

        if (!$process->successful()) {
            // Возможно, коммит уже существует или нет изменений
            $errorOutput = $process->errorOutput();
            if (strpos($errorOutput, 'nothing to commit') !== false) {
                $this->warn('  ⚠️  Нет изменений для коммита');
                return $commitMessage;
            }
            throw new \Exception("Ошибка создания коммита:\n" . $errorOutput);
        }

        $this->info("  ✅ Коммит создан: {$commitMessage}");
        $this->newLine();
        return $commitMessage;
    }

    /**
     * Отправка в репозиторий
     */
    protected function pushToRepository(bool $dryRun): void
    {
        $this->info('📤 Шаг 6: Отправка в репозиторий...');

        // Определяем текущую ветку
        $branchProcess = Process::run('git rev-parse --abbrev-ref HEAD');
        $branch = trim($branchProcess->output()) ?: 'main';

        $forcePush = $this->option('force');

        if ($forcePush) {
            $this->warn('  ⚠️  ВНИМАНИЕ: Используется принудительная отправка (--force)');
            $this->warn('  ⚠️  Это перезапишет удаленную ветку и может удалить коммиты!');
        }

        if ($dryRun) {
            $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
            $this->line("  [DRY-RUN] Выполнение: {$pushCommand}");
            return;
        }

        // Увеличиваем таймаут для git push (большие файлы могут требовать больше времени)
        $pushCommand = $forcePush ? "git push --force origin {$branch}" : "git push origin {$branch}";
        $process = Process::timeout(300) // 5 минут
            ->run($pushCommand);

        if (!$process->successful()) {
            $errorOutput = $process->errorOutput();

            // Проверяем, нужно ли установить upstream
            if (str_contains($errorOutput, 'no upstream branch')) {
                $this->line("  🔄 Установка upstream для ветки {$branch}...");
                $upstreamCommand = $forcePush ? "git push --force -u origin {$branch}" : "git push -u origin {$branch}";
                $process = Process::timeout(300)
                    ->run($upstreamCommand);

                if (!$process->successful()) {
                    throw new \Exception("Ошибка отправки в репозиторий:\n" . $process->errorOutput());
                }
            } else {
                // Проверяем на таймаут
                if (str_contains($errorOutput, 'timeout') || str_contains($errorOutput, 'exceeded')) {
                    throw new \Exception(
                        "Таймаут отправки в репозиторий. Возможно, файлы слишком большие.\n" .
                        "Проверьте, нет ли в коммите больших файлов (архивы, изображения и т.д.).\n" .
                        "Рекомендуется добавить их в .gitignore."
                    );
                }

                // Если обычный push не прошел из-за non-fast-forward, предлагаем force
                if (str_contains($errorOutput, 'non-fast-forward') && !$forcePush) {
                    throw new \Exception(
                        "Ошибка отправки в репозиторий: локальная ветка отстает от удаленной.\n" .
                        "Если вы делаете откат, используйте флаг --force:\n" .
                        "php artisan deploy --force --insecure\n" .
                        "⚠️  ВНИМАНИЕ: --force перезапишет удаленную ветку!"
                    );
                }

                throw new \Exception("Ошибка отправки в репозиторий:\n" . $errorOutput);
            }
        }

        $this->info("  ✅ Изменения отправлены в ветку: {$branch}" . ($forcePush ? " (force push)" : ""));
        $this->newLine();
    }

    /**
     * Отправка POST запроса на сервер
     */
    protected function sendDeployRequest(): void
    {
        $this->info('🌐 Шаг 7: Отправка запроса на сервер...');

        $serverUrl = env('DEPLOY_SERVER_URL');
        $deployToken = env('DEPLOY_TOKEN');

        if (!$serverUrl) {
            $this->warn('  ⚠️  DEPLOY_SERVER_URL не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_SERVER_URL и DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        if (!$deployToken) {
            $this->warn('  ⚠️  DEPLOY_TOKEN не настроен в .env - пропуск отправки на сервер');
            $this->line('  💡 Добавьте DEPLOY_TOKEN в .env для автоматического деплоя');
            $this->newLine();
            return;
        }

        // Получаем текущий commit hash
        $commitProcess = Process::run('git rev-parse HEAD');
        $commitHash = trim($commitProcess->output()) ?: 'unknown';

        // Формируем правильный URL
        $deployUrl = rtrim($serverUrl, '/');

        // Убираем /api/deploy если он уже есть в URL
        if (str_contains($deployUrl, '/api/deploy')) {
            $pos = strpos($deployUrl, '/api/deploy');
            $deployUrl = substr($deployUrl, 0, $pos);
            $deployUrl = rtrim($deployUrl, '/');
        }

        // Добавляем /api/deploy
        $deployUrl .= '/api/deploy';

        $this->line("  📡 URL: {$deployUrl}");
        $this->line("  🔑 Commit: " . substr($commitHash, 0, 7));
        $this->line("  🔐 Token: " . (substr($deployToken, 0, 3) . '...' . substr($deployToken, -3)));

        try {
            // Предварительная проверка DNS резолвинга
            $host = parse_url($deployUrl, PHP_URL_HOST);
            if ($host) {
                $this->line("  🔍 Проверка DNS для {$host}...");
                $ip = gethostbyname($host);
                if ($ip === $host) {
                    $this->warn("  ⚠️  Не удалось разрешить DNS для {$host}");
                    $this->line("  💡 Попробуйте проверить интернет-соединение или DNS настройки");
                } else {
                    $this->line("  ✅ DNS разрешен: {$host} → {$ip}");
                }
            }

            $httpClient = Http::timeout(300); // 5 минут таймаут

            // Отключить проверку SSL для локальной разработки (если указана опция)
            if ($this->option('insecure') || env('APP_ENV') === 'local') {
                $httpClient = $httpClient->withoutVerifying();
                if ($this->option('insecure')) {
                    $this->warn('  ⚠️  Проверка SSL сертификата отключена (--insecure)');
                } else {
                    $this->line('  ℹ️  Проверка SSL отключена (локальное окружение)');
                }
            }

            // Дополнительные настройки для cURL при проблемах с SSL
            $curlOptions = [];
            if ($this->option('insecure')) {
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = false;
            }

            // Пробуем разные версии TLS
            $curlOptions[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;

            // Увеличиваем таймауты
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = 30;
            $curlOptions[CURLOPT_TIMEOUT] = 300;

            // Разрешаем редиректы
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
            $curlOptions[CURLOPT_MAXREDIRS] = 5;

            // Настройки для решения проблем с DNS
            // Принудительно используем IPv4 (может помочь при проблемах с IPv6)
            $curlOptions[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
            
            // Увеличиваем таймаут DNS резолвинга
            $curlOptions[CURLOPT_DNS_CACHE_TIMEOUT] = 60;
            
            // Используем системный DNS резолвер
            $curlOptions[CURLOPT_DNS_USE_GLOBAL_CACHE] = true;

            $response = $httpClient->withOptions($curlOptions)
                ->withHeaders([
                    'X-Deploy-Token' => $deployToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'WOW-Spin-Deploy/1.0',
                ])
                ->post($deployUrl, [
                    'commit_hash' => $commitHash,
                    'repository' => $this->gitRepository,
                    'branch' => trim(Process::run('git rev-parse --abbrev-ref HEAD')->output() ?: 'main'),
                    'deployed_by' => get_current_user(),
                    'timestamp' => now()->toDateTimeString(),
                    'run_seeders' => $this->option('with-seed'),
                ]);

            // Проверяем статус ответа
            if ($response->successful()) {
                $data = $response->json();

                $this->newLine();
                $this->info('  ✅ Сервер ответил успешно:');

                if (isset($data['data'])) {
                    $dataArray = $data['data'];

                    if (isset($dataArray['php_path'])) {
                        $this->line("     PHP: {$dataArray['php_path']} (v{$dataArray['php_version']})");
                    }

                    if (isset($dataArray['git_pull'])) {
                        $this->line("     Git Pull: {$dataArray['git_pull']}");
                    }

                    if (isset($dataArray['composer_install'])) {
                        $this->line("     Composer: {$dataArray['composer_install']}");
                    }

                    if (isset($dataArray['migrations'])) {
                        $migrations = $dataArray['migrations'];
                        if (is_array($migrations) && isset($migrations['status'])) {
                            if ($migrations['status'] === 'success') {
                                $this->line("     Миграции: " . ($migrations['message'] ?? 'успешно'));
                            } else {
                                $this->warn("     Миграции: ошибка - " . ($migrations['error'] ?? 'неизвестная ошибка'));
                            }
                        }
                    }

                    if (isset($dataArray['seeders'])) {
                        $seeders = $dataArray['seeders'];
                        if (is_array($seeders) && isset($seeders['status'])) {
                            if ($seeders['status'] === 'skipped') {
                                $this->line("     Seeders: " . ($seeders['message'] ?? 'пропущены'));
                            } elseif ($seeders['status'] === 'success') {
                                $this->line("     Seeders: " . ($seeders['message'] ?? 'успешно'));
                            } elseif ($seeders['status'] === 'partial') {
                                $this->warn("     Seeders: " . ($seeders['message'] ?? 'частично выполнены'));
                            } else {
                                $this->warn("     Seeders: ошибка - " . ($seeders['error'] ?? 'неизвестная ошибка'));
                            }
                        }
                    }

                    if (isset($dataArray['duration_seconds'])) {
                        $this->line("     Время выполнения: {$dataArray['duration_seconds']}с");
                    }

                    if (isset($dataArray['deployed_at'])) {
                        $this->line("     Дата: {$dataArray['deployed_at']}");
                    }
                } else {
                    $this->line("     Ответ: " . json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                $errorData = $response->json();
                throw new \Exception(
                    "Ошибка деплоя на сервере (HTTP {$response->status()}): " .
                    ($errorData['message'] ?? $response->body())
                );
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMessage = $e->getMessage();
            
            // Детальная диагностика ошибки
            $this->newLine();
            $this->error('❌ Ошибка подключения к серверу');
            $this->line("  📡 URL: {$deployUrl}");
            $this->line("  🔍 Ошибка: {$errorMessage}");
            
            // Проверяем тип ошибки и даем рекомендации
            if (str_contains($errorMessage, 'Could not resolve host') || str_contains($errorMessage, 'cURL error 6')) {
                $this->newLine();
                $this->line('  💡 Проблема с DNS резолвингом. Возможные решения:');
                $this->line('     1. Проверьте интернет-соединение');
                $this->line('     2. Проверьте правильность DEPLOY_SERVER_URL в .env');
                $this->line('     3. Попробуйте выполнить команду позже (возможны временные проблемы с DNS)');
                $this->line('     4. Проверьте настройки DNS на вашем компьютере');
                $this->line('     5. Попробуйте использовать IP адрес вместо домена');
                $this->newLine();
                
                // Пробуем резолвить DNS вручную
                $host = parse_url($deployUrl, PHP_URL_HOST);
                if ($host) {
                    $this->line("  🔍 Попытка резолва DNS для {$host}...");
                    $ip = @gethostbyname($host);
                    if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $this->line("  ✅ DNS резолвится: {$host} → {$ip}");
                        $this->line("  💡 Попробуйте временно использовать IP: " . str_replace($host, $ip, $deployUrl));
                    } else {
                        $this->warn("  ❌ DNS не резолвится: {$host}");
                        $this->line("  💡 Проверьте доступность домена через браузер или ping");
                    }
                }
            } elseif (str_contains($errorMessage, 'Connection was reset') || str_contains($errorMessage, 'cURL error 35')) {
                $this->newLine();
                $this->warn('  💡 Возможные причины:');
                $this->line('     1. Проблема с SSL/TLS сертификатом на сервере');
                $this->line('     2. Несовместимость версий TLS между клиентом и сервером');
                $this->line('     3. Файрвол или прокси блокирует соединение');
                $this->line('     4. Сервер недоступен или перегружен');
                $this->newLine();
                $this->line('  🔧 Рекомендации:');
                $this->line('     - Проверьте доступность сервера: curl -I ' . $deployUrl);
                $this->line('     - Проверьте SSL сертификат: openssl s_client -connect ' . parse_url($deployUrl, PHP_URL_HOST) . ':443');
                $this->line('     - Попробуйте использовать HTTP вместо HTTPS (только для тестирования)');
                $this->line('     - Проверьте настройки файрвола на сервере');
            } elseif (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'timed out')) {
                $this->newLine();
                $this->warn('  💡 Возможные причины:');
                $this->line('     1. Сервер не отвечает в течение 5 минут');
                $this->line('     2. Медленное интернет-соединение');
                $this->line('     3. Сервер перегружен');
            } elseif (str_contains($errorMessage, 'SSL') || str_contains($errorMessage, 'certificate')) {
                $this->newLine();
                $this->warn('  💡 Проблема с SSL сертификатом');
                $this->line('     Попробуйте использовать флаг --insecure (уже использован)');
                $this->line('     Или проверьте валидность SSL сертификата на сервере');
            }

            throw new \Exception("Не удалось подключиться к серверу: {$errorMessage}");
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка отправки запроса');
            $this->line("  🔍 Детали: " . $e->getMessage());

            if ($this->option('verbose')) {
                $this->line("  📋 Trace: " . $e->getTraceAsString());
            }

            throw new \Exception("Ошибка отправки запроса: " . $e->getMessage());
        }

        $this->newLine();
    }

    /**
     * Обновление версии приложения для сброса кеша Telegram miniApp
     */
    protected function updateAppVersion(): void
    {
        try {
            // Получаем хеш последнего коммита
            $process = Process::run('git rev-parse --short HEAD');
            $gitHash = trim($process->output());
            
            if ($process->successful() && !empty($gitHash)) {
                // Используем git hash как версию
                $version = $gitHash;
            } else {
                // Если не удалось получить git hash, используем timestamp
                $version = (string)(int)(microtime(true) * 1000);
            }
            
            // Обновляем .env файл
            $envPath = base_path('.env');
            if (File::exists($envPath)) {
                $envContent = File::get($envPath);
                
                // Заменяем или добавляем APP_VERSION
                if (preg_match('/^APP_VERSION=.*$/m', $envContent)) {
                    $envContent = preg_replace('/^APP_VERSION=.*$/m', "APP_VERSION={$version}", $envContent);
                } else {
                    $envContent .= "\nAPP_VERSION={$version}\n";
                }
                
                File::put($envPath, $envContent);
                $this->line("  ✅ Версия приложения обновлена: {$version}");
            }
        } catch (\Exception $e) {
            // Не критично, просто логируем
            $this->warn("  ⚠️  Не удалось обновить версию: " . $e->getMessage());
        }
    }
}

