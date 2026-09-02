<?php

namespace Core\Exceptions;

use Core\Http\Request;
use Core\Http\Response;
use Throwable;

class ExceptionHandler
{
    protected bool $debug;

    public function __construct(bool $debug = true)
    {
        $this->debug = $debug;
    }

    public function register(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e) {
            $this->render(Request::capture(), $e)->send();
            exit(1);
        });
    }

    public function render(Request $request, Throwable $e): Response
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        if ($request->wantsJson() || $request->isJson()) {
            $payload = [
                'error' => true,
                'status' => $statusCode,
                'message' => $e->getMessage(),
            ];

            if ($this->debug) {
                $payload['exception'] = get_class($e);
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
                $payload['trace'] = explode("\n", $e->getTraceAsString());
            }

            return Response::json($payload, $statusCode);
        }

        // Check if custom error view exists
        $viewsPath = dirname(__DIR__, 2) . '/resources/views/errors';
        $viewFile = "{$viewsPath}/{$statusCode}.php";
        if (!file_exists($viewFile)) {
            $viewFile = "{$viewsPath}/500.php";
        }

        if (file_exists($viewFile) && !$this->debug) {
            ob_start();
            $message = $e->getMessage();
            include $viewFile;
            $content = ob_get_clean();
            return Response::html($content, $statusCode);
        }

        // Pretty debug HTML page
        $html = $this->renderDebugPage($e, $statusCode);
        return Response::html($html, $statusCode);
    }

    protected function renderDebugPage(Throwable $e, int $statusCode): string
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $class = htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8');
        $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
        $line = $e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SyntaxCore Error - {$class}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; background: #1e293b; border-radius: 12px; border: 1px solid #334155; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
        .header { background: #ef4444; padding: 1.5rem 2rem; color: #ffffff; }
        .header h1 { margin: 0 0 0.5rem 0; font-size: 1.5rem; }
        .header p { margin: 0; font-size: 1rem; opacity: 0.95; word-break: break-word; }
        .body { padding: 2rem; }
        .location { background: #0f172a; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.95rem; margin-bottom: 1.5rem; border: 1px solid #334155; }
        .location span { color: #38bdf8; }
        h2 { font-size: 1.1rem; color: #94a3b8; margin-top: 0; text-transform: uppercase; letter-spacing: 0.05em; }
        pre { background: #0f172a; padding: 1.25rem; border-radius: 8px; overflow-x: auto; font-family: monospace; font-size: 0.85rem; line-height: 1.5; color: #e2e8f0; border: 1px solid #334155; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{$statusCode} | {$class}</h1>
            <p>{$message}</p>
        </div>
        <div class="body">
            <h2>Location</h2>
            <div class="location">
                <span>{$file}</span>:<b>{$line}</b>
            </div>
            <h2>Stack Trace</h2>
            <pre>{$trace}</pre>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
