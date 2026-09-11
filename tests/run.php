<?php

/**
 * SyntaxCore Architecture & Integration Test Runner
 * Zero-dependency, lightweight, native test suite.
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private string $currentSuite = '';

    public function suite(string $name): void
    {
        $this->currentSuite = $name;
        echo "\n=== {$name} ===\n";
    }

    public function test(string $name, callable $callback): void
    {
        try {
            $callback($this);
            echo "  [PASS] {$name}\n";
            $this->passed++;
        } catch (\Throwable $e) {
            echo "  [FAIL] {$name}\n";
            echo "         " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
            $this->failed++;
        }
    }

    public function assert(bool $condition, string $message = 'Assertion failed'): void
    {
        if (!$condition) {
            throw new \AssertionError($message);
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true);
            throw new \AssertionError($msg);
        }
    }

    public function assertContains(string $needle, string $haystack, string $message = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            $msg = $message ?: "String does not contain expected substring '{$needle}'";
            throw new \AssertionError($msg);
        }
    }

    public function assertThrows(string $exceptionClass, callable $callback, string $message = ''): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            if ($e instanceof $exceptionClass) {
                return;
            }
            throw new \AssertionError("Expected {$exceptionClass}, but caught " . get_class($e));
        }
        throw new \AssertionError($message ?: "Expected {$exceptionClass} was not thrown");
    }

    public function summary(): int
    {
        echo "\n==================================================\n";
        echo "Total: " . ($this->passed + $this->failed) . " | Passed: {$this->passed} | Failed: {$this->failed}\n";
        echo "==================================================\n";
        return $this->failed === 0 ? 0 : 1;
    }
}

$t = new TestRunner();

// ==========================================
// 1. RUNTIME INTEGRATION TESTS (Priority 6)
// ==========================================
$t->suite('Runtime Integration');

$t->test('Browser Request -> Kernel -> Router -> Controller -> Auto View -> HTML Response', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $request = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/',
    ]);

    $response = $kernel->handle($request);

    $t->assertEquals(200, $response->getStatusCode());
    $t->assertContains('text/html', $response->getHeaders()['Content-Type'] ?? '');
    $t->assertContains('SyntaxCore', $response->getContent());
    $t->assertContains('/assets/vendor/bootstrap/css/bootstrap.min.css', $response->getContent());
    $t->assertContains('/assets/vendor/bootstrap/js/bootstrap.bundle.min.js', $response->getContent());
    $t->assertContains('/assets/css/app.css', $response->getContent());
    $t->assertContains('/assets/js/app.js', $response->getContent());
});

$t->test('API Request -> Kernel -> Router -> JSON Response', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $request = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/api/v1/status',
    ]);

    $response = $kernel->handle($request);

    $t->assertEquals(200, $response->getStatusCode());
    $t->assertContains('application/json', $response->getHeaders()['Content-Type'] ?? '');

    $data = json_decode($response->getContent(), true);
    $t->assert(is_array($data), 'Response should be valid JSON');
    $t->assertEquals('success', $data['status'] ?? null);
    $t->assertEquals('v1', $data['api_version'] ?? null);
});

$t->test('SyntaxCore JavaScript API Client asset exists and contains fetch wrapper', function ($t) use ($baseDir) {
    $jsPath = $baseDir . '/public/assets/js/app.js';
    $t->assert(file_exists($jsPath), 'public/assets/js/app.js must exist');
    $content = file_get_contents($jsPath);
    $t->assertContains('SyntaxCore', $content);
    $t->assertContains('fetch(', $content);
});

// ==========================================
// 2. ROUTING ARCHITECTURE (Priority 7)
// ==========================================
$t->suite('Routing Architecture');

$t->test('Router supports all HTTP verbs and group prefixes', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $router = new \Core\Routing\Router($app);

    $router->get('/test-get', fn() => 'get');
    $router->post('/test-post', fn() => 'post');
    $router->put('/test-put', fn() => 'put');
    $router->delete('/test-delete', fn() => 'delete');

    $router->group(['prefix' => 'admin/v1'], function ($r) {
        $r->get('/dashboard', fn() => 'dashboard');
    });

    $res1 = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test-get']));
    $t->assertEquals('get', $res1->getContent());

    $res2 = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/test-post']));
    $t->assertEquals('post', $res2->getContent());

    $res3 = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/test-put']));
    $t->assertEquals('put', $res3->getContent());

    $res4 = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/test-delete']));
    $t->assertEquals('delete', $res4->getContent());

    $res5 = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/v1/dashboard']));
    $t->assertEquals('dashboard', $res5->getContent());
});

$t->test('Router resolves route parameters', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $router = new \Core\Routing\Router($app);

    $router->get('/users/{id}', function (\Core\Http\Request $req, $id) {
        return ['userId' => $id];
    });

    $res = $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/42']));
    $data = json_decode($res->getContent(), true);
    $t->assertEquals('42', $data['userId'] ?? null);
});

$t->test('Router throws HttpException 404 for unknown route', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $router = new \Core\Routing\Router($app);

    $t->assertThrows(\Core\Exceptions\HttpException::class, function () use ($router) {
        $router->dispatch(new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/unknown-path']));
    });
});

// ==========================================
// 3. MIDDLEWARE PIPELINE (Priority 7)
// ==========================================
$t->suite('Middleware Pipeline');

$t->test('Global and Route Middleware execute in order with alias resolution and priority', function ($t) use ($baseDir) {
    $executionLog = [];

    $mGlobal = new class($executionLog) implements \Core\Middleware\MiddlewareInterface {
        public function __construct(private array &$log) {}
        public function handle(\Core\Http\Request $request, \Closure $next): mixed {
            $this->log[] = 'global';
            return $next($request);
        }
    };

    $mAuth = new class($executionLog) implements \Core\Middleware\MiddlewareInterface {
        public function __construct(private array &$log) {}
        public function handle(\Core\Http\Request $request, \Closure $next): mixed {
            $this->log[] = 'auth_alias';
            return $next($request);
        }
    };

    $mSecond = new class($executionLog) implements \Core\Middleware\MiddlewareInterface {
        public function __construct(private array &$log) {}
        public function handle(\Core\Http\Request $request, \Closure $next): mixed {
            $this->log[] = 'second_alias';
            return $next($request);
        }
    };

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = new \Core\Application\Kernel($app, new \Core\Routing\Router($app));

    $kernel->setMiddleware([$mGlobal]);
    $kernel->setRouteMiddleware([
        'auth' => $mAuth,
        'second' => $mSecond,
    ]);
    // Priority: auth first, then second
    $kernel->setMiddlewarePriority([get_class($mAuth), get_class($mSecond)]);

    $router = $kernel->getRouter();
    $router->get('/mw-test', function () use (&$executionLog) {
        $executionLog[] = 'controller';
        return ['ok' => true];
    })->middleware('second', 'auth'); // registered in reverse order

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/mw-test']);
    $kernel->handle($req);

    $t->assertEquals(['global', 'auth_alias', 'second_alias', 'controller'], $executionLog);
});

// ==========================================
// 4. CONTROLLER & VIEW RESOLUTION (Priority 4 & 7)
// ==========================================
$t->suite('Controller & View Convention');

$t->test('HomeController resolves to web/home/index automatically', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $controller = new \App\Controllers\Web\HomeController();

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
    $res = $controller->index($req);

    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('SyntaxCore', $res->getContent());
});

$t->test('Explicit view rendering works alongside convention', function ($t) use ($baseDir) {
    $controller = new class extends \Core\Controller\Controller {
        public function explicit() {
            return $this->view('web.home.index', ['appName' => 'ExplicitOverrideApp']);
        }
    };

    $res = $controller->explicit();
    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('ExplicitOverrideApp', $res->getContent());
});

// ==========================================
// 5. DATABASE SAFETY & CONTRACT (Priority 2, 3 & 7)
// ==========================================
$t->suite('Database Safety & Contract');

$t->test('Identifier escaping and validation rejects SQL injection attempts', function ($t) {
    // Valid identifiers
    $t->assertEquals('`users`', \Core\Database\Model::escapeIdentifier('users'));
    $t->assertEquals('`first_name`', \Core\Database\Model::escapeIdentifier('first_name'));

    // Injections
    $t->assertThrows(\InvalidArgumentException::class, function () {
        \Core\Database\Model::escapeIdentifier('users; DROP TABLE users;--');
    });

    $t->assertThrows(\InvalidArgumentException::class, function () {
        \Core\Database\Model::escapeIdentifier('col` = 1 OR 1=1 --');
    });

    $t->assertThrows(\InvalidArgumentException::class, function () {
        \Core\Database\Model::escapeIdentifier('table.column');
    });
});

$t->test('Operator allowlist rejects illegal operators', function ($t) {
    $dummyModel = new class extends \Core\Database\Model {
        protected ?string $table = 'items';
    };

    $t->assertThrows(\InvalidArgumentException::class, function () use ($dummyModel) {
        $dummyModel::where('name', 'UNION SELECT', 'foo');
    });

    $t->assertThrows(\InvalidArgumentException::class, function () use ($dummyModel) {
        $dummyModel::where('id', 'OR 1=1', '1');
    });
});

$t->test('Sort direction allowlist only accepts ASC or DESC', function ($t) {
    $t->assertEquals('ASC', \Core\Database\Model::validateDirection('asc'));
    $t->assertEquals('DESC', \Core\Database\Model::validateDirection('DESC '));

    $t->assertThrows(\InvalidArgumentException::class, function () {
        \Core\Database\Model::validateDirection('SLEEP(5)');
    });
});

$t->test('Fillable protects mass-assignment and rejects unfillable input', function ($t) {
    $userModel = new class(['name' => 'John', 'role' => 'admin', 'id' => 999]) extends \Core\Database\Model {
        protected array $fillable = ['name'];
    };

    $attrs = $userModel->toArray();
    $t->assertEquals('John', $attrs['name'] ?? null);
    $t->assert(!isset($attrs['role']), 'Unfillable attribute role must be filtered out by fill()');
    $t->assert(!isset($attrs['id']), 'Unfillable attribute id must be filtered out by fill()');
});

$t->test('forceFill intentionally bypasses fillable for trusted database hydration', function ($t) {
    $model = new class extends \Core\Database\Model {
        protected array $fillable = ['name'];
    };

    $model->forceFill(['id' => 42, 'name' => 'Admin', 'role' => 'superadmin']);
    $t->assertEquals(42, $model->id);
    $t->assertEquals('Admin', $model->name);
    $t->assertEquals('superadmin', $model->role);
});

// ==========================================
// 6. ASSET & COMPOSER AUDIT (Priority 1 & 5)
// ==========================================
$t->suite('Asset & Composer Audit');

$t->test('Composer identity is valid and properly configured', function ($t) use ($baseDir) {
    $composer = json_decode(file_get_contents($baseDir . '/composer.json'), true);
    $t->assertEquals('syntaxbabi/syntaxcore', $composer['name'] ?? null);
    $t->assertEquals('project', $composer['type'] ?? null);
    $t->assertEquals('app/', $composer['autoload']['psr-4']['App\\'] ?? null);
    $t->assertEquals('core/', $composer['autoload']['psr-4']['Core\\'] ?? null);
});

$t->test('Asset contract directories and files are established', function ($t) use ($baseDir) {
    // Source assets
    $t->assert(is_dir($baseDir . '/resources/assets'), 'resources/assets must exist');
    $t->assert(file_exists($baseDir . '/resources/assets/css/app.css'), 'resources/assets/css/app.css must exist');
    $t->assert(file_exists($baseDir . '/resources/assets/js/app.js'), 'resources/assets/js/app.js must exist');

    // Public browser-accessible assets
    $t->assert(file_exists($baseDir . '/public/assets/css/app.css'), 'public/assets/css/app.css must exist');
    $t->assert(file_exists($baseDir . '/public/assets/js/app.js'), 'public/assets/js/app.js must exist');
    $t->assert(file_exists($baseDir . '/public/assets/vendor/bootstrap/css/bootstrap.min.css'), 'bootstrap.min.css must exist');
    $t->assert(file_exists($baseDir . '/public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js'), 'bootstrap.bundle.min.js must exist');
});

// ==========================================
// 7. CSRF PROTECTION ARCHITECTURE (Priority 1)
// ==========================================
$t->suite('CSRF Protection Architecture');

$t->test('Csrf service generates and verifies 64-character token in session', function ($t) {
    $token = \Core\Security\Csrf::token();
    $t->assert(is_string($token) && strlen($token) === 64, 'Token must be 64-character hex string');
    $t->assert(\Core\Security\Csrf::validate($token), 'Valid token must pass validation');

    $t->assert(!\Core\Security\Csrf::validate('invalid-token'), 'Invalid token must fail validation');
    $t->assert(!\Core\Security\Csrf::validate(''), 'Empty token must fail validation');
    $t->assert(!\Core\Security\Csrf::validate(null), 'Null token must fail validation');

    $regenerated = \Core\Security\Csrf::regenerateToken();
    $t->assert($regenerated !== $token, 'Regenerated token must differ from old token');
    $t->assert(\Core\Security\Csrf::validate($regenerated), 'Regenerated token must validate');
    $t->assert(!\Core\Security\Csrf::validate($token), 'Old token must no longer validate');
});

$t->test('VerifyCsrfToken middleware blocks POST without token with HTTP 419', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $req = new \Core\Http\Request([], ['email' => 'test@example.com'], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/login',
    ]);
    $res = $kernel->handle($req);

    $t->assertEquals(419, $res->getStatusCode(), 'POST without CSRF token must return 419');
    $t->assertContains('CSRF token mismatch', $res->getContent());
});

$t->test('VerifyCsrfToken middleware accepts valid token via input or header', function ($t) use ($baseDir) {
    $token = \Core\Security\Csrf::token();
    $middleware = new \App\Middleware\VerifyCsrfToken();

    // 1. Safe GET request passes without token
    $getReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET']);
    $getRes = $middleware->handle($getReq, fn() => 'next-ok');
    $t->assertEquals('next-ok', $getRes);

    // 2. POST with valid _token input passes
    $postReq = new \Core\Http\Request([], ['_token' => $token], ['REQUEST_METHOD' => 'POST']);
    $postRes = $middleware->handle($postReq, fn() => 'next-ok');
    $t->assertEquals('next-ok', $postRes);

    // 3. POST with valid X-CSRF-TOKEN header passes
    $headerReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'POST',
        'HTTP_X_CSRF_TOKEN' => $token,
    ]);
    $headerRes = $middleware->handle($headerReq, fn() => 'next-ok');
    $t->assertEquals('next-ok', $headerRes);
});

$t->test('VerifyCsrfToken protects all state-changing methods PUT, PATCH, DELETE', function ($t) {
    $token = \Core\Security\Csrf::token();
    $middleware = new \App\Middleware\VerifyCsrfToken();

    foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
        // Without token -> 419
        $reqWithout = new \Core\Http\Request([], [], ['REQUEST_METHOD' => $method]);
        $resWithout = $middleware->handle($reqWithout, fn() => 'ok');
        $t->assert($resWithout instanceof \Core\Http\Response, "{$method} without CSRF must return Response");
        $t->assertEquals(419, $resWithout->getStatusCode(), "{$method} without CSRF must return 419");

        // With token -> passes
        $reqWith = new \Core\Http\Request([], ['_token' => $token], ['REQUEST_METHOD' => $method]);
        $resWith = $middleware->handle($reqWith, fn() => 'ok');
        $t->assertEquals('ok', $resWith, "{$method} with CSRF must pass");
    }
});

$t->test('Global CSRF helpers csrf_token() and csrf_field() work consistently', function ($t) {
    $t->assert(function_exists('csrf_token'), 'csrf_token() helper must exist');
    $t->assert(function_exists('csrf_field'), 'csrf_field() helper must exist');

    $token = csrf_token();
    $t->assert(is_string($token) && strlen($token) === 64, 'csrf_token() must return 64-char string');
    $t->assertEquals(\Core\Security\Csrf::token(), $token, 'csrf_token() must match Csrf::token()');

    $field = csrf_field();
    $t->assertContains('name="_token"', $field, 'csrf_field() must contain name="_token"');
    $t->assertContains($token, $field, 'csrf_field() must contain current token value');
    $t->assertContains('<input type="hidden"', $field, 'csrf_field() must be hidden input');
});

// ==========================================
// 8. SESSION LIFECYCLE & CENTRALIZATION (Priority 1)
// ==========================================
$t->suite('Session Lifecycle & Centralization');

$t->test('Core Session manager handles lifecycle and state', function ($t) {
    \Core\Session\Session::start();
    $t->assert(\Core\Session\Session::isStarted(), 'Session must be started');

    \Core\Session\Session::set('test_key', 'test_value');
    $t->assert(\Core\Session\Session::has('test_key'), 'Session must have test_key');
    $t->assertEquals('test_value', \Core\Session\Session::get('test_key'));
    $t->assertEquals('default', \Core\Session\Session::get('non_existent', 'default'));

    \Core\Session\Session::remove('test_key');
    $t->assert(!\Core\Session\Session::has('test_key'), 'test_key must be removed');

    $all = \Core\Session\Session::all();
    $t->assert(is_array($all), 'Session::all() must return array');
});

$t->test('CSRF and AuthService both use centralized Session', function ($t) {
    // Both read/write through Core\Session\Session
    $token = \Core\Security\Csrf::token();
    $t->assertEquals($token, \Core\Session\Session::get(\Core\Security\Csrf::TOKEN_KEY));

    $auth = new \App\Services\AuthService();
    $auth->logout();
    $t->assert(empty(\Core\Session\Session::get('auth.user_id')));
});

$t->test('CSRF token regenerates on login and logout transitions', function ($t) {
    // Seed user for test if not exists
    $user = \App\Models\User::findByEmail('admin@syntaxcore.test');
    if (!$user) {
        $user = new \App\Models\User([
            'name' => 'Admin Test',
            'email' => 'admin@syntaxcore.test',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $user->setPassword('secretpassword123');
        $user->save();
    }

    // 1. Initial anonymous token
    $tokenA = \Core\Security\Csrf::token();
    $t->assert(!empty($tokenA));

    // 2. Login transition regenerates token
    $auth = new \App\Services\AuthService();
    $auth->attempt('admin@syntaxcore.test', 'secretpassword123');
    $tokenB = \Core\Security\Csrf::token();

    $t->assert($tokenB !== $tokenA, 'Token must be regenerated on login');
    $t->assert(\Core\Security\Csrf::validate($tokenB), 'New token B must be valid');
    $t->assert(!\Core\Security\Csrf::validate($tokenA), 'Old token A must no longer be valid');

    // 3. Logout transition regenerates token for new anonymous session
    $auth->logout();
    $tokenC = \Core\Security\Csrf::token();

    $t->assert($tokenC !== $tokenB, 'Token must be regenerated on logout');
    $t->assert(\Core\Security\Csrf::validate($tokenC), 'New token C must be valid');
    $t->assert(!\Core\Security\Csrf::validate($tokenB), 'Old token B must no longer be valid');
});

$t->test('JavaScript API client includes csrfToken method and attaches X-CSRF-TOKEN', function ($t) use ($baseDir) {
    $jsContent = file_get_contents($baseDir . '/public/assets/js/app.js');
    $t->assertContains('csrfToken()', $jsContent, 'JS client must have csrfToken method');
    $t->assertContains('X-CSRF-TOKEN', $jsContent, 'JS client must attach X-CSRF-TOKEN');
    $t->assertContains('meta[name="csrf-token"]', $jsContent, 'JS client must query meta csrf-token');
});

// ==========================================
// 9. ADMIN AUTHENTICATION MODULE (Priority 5 & 6)
// ==========================================
$t->suite('Admin Authentication Module');

$t->test('Admin User model creation and password hashing', function ($t) {
    $existing = \App\Models\User::findByEmail('admin@syntaxcore.test');
    if ($existing) {
        $existing->delete();
    }

    $user = new \App\Models\User([
        'name' => 'Admin Test',
        'email' => 'admin@syntaxcore.test',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $user->setPassword('secretpassword123');
    $t->assert($user->save(), 'User must be saved');
    $t->assert($user->verifyPassword('secretpassword123'), 'Password must verify');
    $t->assert(!$user->verifyPassword('wrongpassword'), 'Wrong password must not verify');

    // Sensitive data exposure test: password must not appear in toArray()
    $array = $user->toArray();
    $t->assert(!isset($array['password']), 'Password must never appear in toArray()');
});

$t->test('AuthService credentials verification and session storage', function ($t) {
    $auth = new \App\Services\AuthService();

    // Unknown user fails
    $t->assert(!$auth->attempt('unknown@syntaxcore.test', 'password'), 'Unknown user must fail');

    // Invalid password fails
    $t->assert(!$auth->attempt('admin@syntaxcore.test', 'wrongpassword'), 'Wrong password must fail');

    // Valid credentials authenticate successfully
    $t->assert($auth->attempt('admin@syntaxcore.test', 'secretpassword123'), 'Valid credentials must succeed');

    // Session stores only required authentication identity (user_id)
    $t->assert($auth->check(), 'AuthService::check must be true');
    $t->assert(!empty($_SESSION['auth']['user_id']), 'Session must store auth.user_id');
    $t->assert(!isset($_SESSION['auth']['password']), 'Session must not store password');
    $t->assert(!isset($_SESSION['auth']['user']), 'Session must not store full User model');

    // Retrieve user safely
    $user = $auth->user();
    $t->assert($user instanceof \App\Models\User, 'Current user must resolve to User model');
    $t->assertEquals('admin@syntaxcore.test', $user->email);
});

$t->test('Guest access to protected /admin is redirected to /admin/login', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->logout();

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin']);
    $res = $kernel->handle($req);

    $t->assertEquals(302, $res->getStatusCode());
    $t->assertEquals('/admin/login', $res->getHeaders()['Location'] ?? null);
});

$t->test('Guest can access /admin/login and view Bootstrap login form with CSRF token', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->logout();

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/login']);
    $res = $kernel->handle($req);

    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('Admin Login', $res->getContent());
    $t->assertContains('name="_token"', $res->getContent());
    $t->assertContains('type="email"', $res->getContent());
    $t->assertContains('type="password"', $res->getContent());
});

$t->test('POST /admin/login handles valid CSRF with valid and invalid credentials', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->logout();

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $csrfToken = \Core\Security\Csrf::token();

    // 1. Invalid credentials with valid CSRF token -> 422
    $invalidReq = new \Core\Http\Request([], [
        '_token' => $csrfToken,
        'email' => 'admin@syntaxcore.test',
        'password' => 'wrongpassword',
    ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/login']);
    $invalidRes = $kernel->handle($invalidReq);

    $t->assertEquals(422, $invalidRes->getStatusCode());
    $t->assertContains('Invalid credentials.', $invalidRes->getContent());

    // 2. Valid credentials with valid CSRF token -> 302 to /admin
    $validReq = new \Core\Http\Request([], [
        '_token' => $csrfToken,
        'email' => 'admin@syntaxcore.test',
        'password' => 'secretpassword123',
    ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/login']);
    $validRes = $kernel->handle($validReq);

    $t->assertEquals(302, $validRes->getStatusCode());
    $t->assertEquals('/admin', $validRes->getHeaders()['Location'] ?? null);
    $t->assert($auth->check(), 'User must now be authenticated');
});

$t->test('Authenticated user can access /admin and cannot access /admin/login', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->attempt('admin@syntaxcore.test', 'secretpassword123');
    $t->assert($auth->check(), 'Must be authenticated');

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // Access protected /admin
    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin']);
    $res = $kernel->handle($req);

    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('Admin Dashboard', $res->getContent());
    $t->assertContains('Admin Test', $res->getContent());
    $t->assertContains('name="_token"', $res->getContent());

    // Attempt to access guest-only /admin/login
    $loginReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/login']);
    $loginRes = $kernel->handle($loginReq);

    $t->assertEquals(302, $loginRes->getStatusCode());
    $t->assertEquals('/admin', $loginRes->getHeaders()['Location'] ?? null);
});

$t->test('Logout requires CSRF, clears session, and revokes protected access', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->attempt('admin@syntaxcore.test', 'secretpassword123');

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // 1. POST /admin/logout without CSRF -> 419
    $noCsrfReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/logout']);
    $noCsrfRes = $kernel->handle($noCsrfReq);
    $t->assertEquals(419, $noCsrfRes->getStatusCode());
    $t->assert($auth->check(), 'User should still be authenticated after failed CSRF');

    // 2. POST /admin/logout with valid CSRF -> 302 to /admin/login
    $logoutReq = new \Core\Http\Request([], [
        '_token' => \Core\Security\Csrf::token(),
    ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/logout']);
    $logoutRes = $kernel->handle($logoutReq);

    $t->assertEquals(302, $logoutRes->getStatusCode());
    $t->assertEquals('/admin/login', $logoutRes->getHeaders()['Location'] ?? null);
    $t->assert($auth->guest(), 'User must now be guest');

    // 3. Subsequent access to /admin redirects to login
    $adminReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin']);
    $adminRes = $kernel->handle($adminReq);

    $t->assertEquals(302, $adminRes->getStatusCode());
    $t->assertEquals('/admin/login', $adminRes->getHeaders()['Location'] ?? null);

    // Cleanup test user
    $testUser = \App\Models\User::findByEmail('admin@syntaxcore.test');
    if ($testUser) {
        $testUser->delete();
    }
});

// ==========================================
// 10. ROLE-BASED ACCESS CONTROL (RBAC) MODULE
// ==========================================
$t->suite('Role-Based Access Control (RBAC) Architecture');

$t->test('Parameterized middleware resolves route arguments and executes pipeline', function ($t) use ($baseDir) {
    $executionLog = [];

    $mRole = new class($executionLog) implements \Core\Middleware\MiddlewareInterface {
        public function __construct(private array &$log) {}
        public function handle(\Core\Http\Request $request, \Closure $next, ...$roles): mixed {
            $this->log[] = 'role:' . implode(',', $roles);
            return $next($request);
        }
    };

    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = new \Core\Application\Kernel($app, new \Core\Routing\Router($app));

    $kernel->setRouteMiddleware(['role' => $mRole]);

    $router = $kernel->getRouter();
    $router->get('/rbac-param-test', function () use (&$executionLog) {
        $executionLog[] = 'destination';
        return ['ok' => true];
    })->middleware('role:admin,superadmin');

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/rbac-param-test']);
    $kernel->handle($req);

    $t->assertEquals(['role:admin,superadmin', 'destination'], $executionLog);
});

$t->test('RBAC middleware enforces role restrictions (403 for unauthorized, 200 for authorized)', function ($t) use ($baseDir) {
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = new \App\Services\AuthService();

    // 1. Guest request with Accept: application/json -> 401
    $auth->logout();
    $guestReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/users',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $guestRes = $kernel->handle($guestReq);
    $t->assertEquals(401, $guestRes->getStatusCode());

    // 2. Regular User (role: user) accessing /admin/users -> 403 Forbidden
    $auth->attempt('user@syntaxcore.com', 'user123');
    $userReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/users',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $userRes = $kernel->handle($userReq);
    $t->assertEquals(403, $userRes->getStatusCode());

    // 3. Administrator (role: admin) accessing /admin/users -> 200 OK
    $auth->attempt('manager@syntaxcore.com', 'manager123');
    $adminReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/users',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $adminRes = $kernel->handle($adminReq);
    $t->assertEquals(200, $adminRes->getStatusCode());
    $adminData = json_decode($adminRes->getContent(), true);
    $t->assertEquals('success', $adminData['status'] ?? null);

    // Administrator accessing superadmin-only /admin/settings -> 403 Forbidden
    $adminSettingsReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/settings',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $adminSettingsRes = $kernel->handle($adminSettingsReq);
    $t->assertEquals(403, $adminSettingsRes->getStatusCode());

    // 4. Super Administrator (role: superadmin) accessing /admin/settings -> 200 OK
    $auth->attempt('admin@syntaxcore.com', 'admin123');
    $superSettingsReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/settings',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $superSettingsRes = $kernel->handle($superSettingsReq);
    $t->assertEquals(200, $superSettingsRes->getStatusCode());
    $superData = json_decode($superSettingsRes->getContent(), true);
    $t->assertEquals('success', $superData['status'] ?? null);
    $t->assertEquals('Pengaturan Sistem', $superData['module'] ?? null);

    // Logout
    $auth->logout();
});

$t->test('Role and Menu Permission Management CRUD & Safety Architecture', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);

    // Login as Superadmin
    $auth->attempt('admin@syntaxcore.com', 'admin123');

    // 1. GET /admin/roles
    $getReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/roles',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $getRes = $kernel->handle($getReq);
    $t->assertEquals(200, $getRes->getStatusCode());
    $getData = json_decode($getRes->getContent(), true);
    $t->assertEquals('success', $getData['status'] ?? null);
    $t->assert(isset($getData['roles']) && count($getData['roles']) >= 3);
    $t->assert(isset($getData['menus']) && count($getData['menus']) >= 10);

    // 2. POST /admin/roles (Create new test role)
    $token = \Core\Security\Csrf::token();

    $postReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Unit Tester Role',
        'slug' => 'unit-tester-role',
        'level' => 1,
        'description' => 'Temporary test role',
        'menu_ids' => [1, 2, 6],
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/roles',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $postRes = $kernel->handle($postReq);
    $t->assertEquals(201, $postRes->getStatusCode());
    $postData = json_decode($postRes->getContent(), true);
    $t->assertEquals('success', $postData['status'] ?? null);
    $testRoleId = $postData['role']['id'] ?? null;
    $t->assert($testRoleId !== null);
    $t->assertEquals(3, count($postData['role']['menu_ids']));

    // 3. PUT /admin/roles/{id} (Update test role & sync menu permissions)
    $putReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Unit Tester Senior Role',
        'slug' => 'unit-tester-senior',
        'level' => 2,
        'description' => 'Updated temporary test role',
        'menu_ids' => [1, 2, 4, 6],
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => "/admin/roles/{$testRoleId}",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $putRes = $kernel->handle($putReq);
    $t->assertEquals(200, $putRes->getStatusCode());
    $putData = json_decode($putRes->getContent(), true);
    $t->assertEquals('success', $putData['status'] ?? null);
    $t->assertEquals('Unit Tester Senior Role', $putData['role']['name']);
    $t->assertEquals(4, count($putData['role']['menu_ids']));

    // 4. Safety: Superadmin deletion is rejected with 422
    $delSuperReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'DELETE',
        'REQUEST_URI' => '/admin/roles/1',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $delSuperRes = $kernel->handle($delSuperReq);
    $t->assertEquals(422, $delSuperRes->getStatusCode());

    // 5. Safety: Role in use deletion is rejected with 422
    $delInUseReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'DELETE',
        'REQUEST_URI' => '/admin/roles/3',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $delInUseRes = $kernel->handle($delInUseReq);
    $t->assertEquals(422, $delInUseRes->getStatusCode());

    // 6. DELETE /admin/roles/{id} (Delete temporary test role)
    $delReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'DELETE',
        'REQUEST_URI' => "/admin/roles/{$testRoleId}",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $delRes = $kernel->handle($delReq);
    $t->assertEquals(200, $delRes->getStatusCode());

    $auth->logout();
});

$t->test('Activity Logging & Notifications Architecture', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);

    // Login as Superadmin
    $auth->attempt('admin@syntaxcore.com', 'admin123');
    $token = \Core\Security\Csrf::token();

    // 1. Create test activity log & notification via ActivityLogger
    $log = \App\Services\ActivityLogger::log('test.system', 'Pengujian otomatis sistem log aktivitas');
    $t->assert($log->id > 0, 'Activity log must be saved with primary key');

    $notif = \App\Services\ActivityLogger::notify('Test Notifikasi', 'Pesan pengujian sistem notifikasi', 'info');
    $t->assert($notif->id > 0, 'Notification must be saved with primary key');
    $t->assertEquals(0, (int) $notif->is_read, 'New notification must be unread by default');

    // 2. GET /admin/reports
    $getRepReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/reports',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $getRepRes = $kernel->handle($getRepReq);
    $t->assertEquals(200, $getRepRes->getStatusCode());
    $repData = json_decode($getRepRes->getContent(), true);
    $t->assertEquals('success', $repData['status'] ?? null);
    $t->assert(isset($repData['logs']) && count($repData['logs']) > 0);

    // 3. GET /admin/notifications
    $getNotifReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/notifications',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $getNotifRes = $kernel->handle($getNotifReq);
    $t->assertEquals(200, $getNotifRes->getStatusCode());
    $notifData = json_decode($getNotifRes->getContent(), true);
    $t->assertEquals('success', $notifData['status'] ?? null);
    $t->assert($notifData['unread_count'] > 0);

    // 4. POST /admin/notifications/{id}/read
    $readReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/admin/notifications/{$notif->id}/read",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $readRes = $kernel->handle($readReq);
    $t->assertEquals(200, $readRes->getStatusCode());
    $readData = json_decode($readRes->getContent(), true);
    $t->assertEquals('success', $readData['status'] ?? null);

    // 5. POST /admin/notifications/read-all
    $readAllReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/notifications/read-all',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $readAllRes = $kernel->handle($readAllReq);
    $t->assertEquals(200, $readAllRes->getStatusCode());

    // Clean up test records
    $log->delete();
    $notif->delete();

    $auth->logout();
});

$t->test('User Profile Module (GET /admin/profile & PUT /admin/profile)', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);

    // 1. Unauthenticated request: JSON returns 401, browser returns 302 redirect
    $guestApiReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $guestApiRes = $kernel->handle($guestApiReq);
    $t->assertEquals(401, $guestApiRes->getStatusCode(), 'Guest JSON request to /admin/profile must return 401');

    $guestBrowserReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'text/html',
    ]);
    $guestBrowserRes = $kernel->handle($guestBrowserReq);
    $t->assertEquals(302, $guestBrowserRes->getStatusCode(), 'Guest browser request to /admin/profile must redirect (302)');

    // 2. Login as Superadmin
    $auth->attempt('admin@syntaxcore.com', 'admin123');
    $token = \Core\Security\Csrf::token();
    $currentUser = $auth->user();

    // 3. GET /admin/profile
    $getReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $getRes = $kernel->handle($getReq);
    $t->assertEquals(200, $getRes->getStatusCode());
    $getData = json_decode($getRes->getContent(), true);
    $t->assertEquals('success', $getData['status'] ?? null);
    $t->assertEquals('admin@syntaxcore.com', $getData['user']['email'] ?? null);
    $t->assert(isset($getData['recent_activities']), 'Must contain recent_activities');

    // 4. PUT /admin/profile - Validation: invalid email
    $invEmailReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Admin Updated',
        'email' => 'invalid-email-format',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $invEmailRes = $kernel->handle($invEmailReq);
    $t->assertEquals(422, $invEmailRes->getStatusCode());

    // 5. PUT /admin/profile - Validation: wrong current password
    $wrongPassReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Admin Updated',
        'email' => 'admin@syntaxcore.com',
        'current_password' => 'wrongpassword',
        'new_password' => 'newpassword123',
        'confirm_password' => 'newpassword123',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $wrongPassRes = $kernel->handle($wrongPassReq);
    $t->assertEquals(422, $wrongPassRes->getStatusCode());

    // 6. PUT /admin/profile - Validation: password confirmation mismatch
    $mismatchReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Admin Updated',
        'email' => 'admin@syntaxcore.com',
        'current_password' => 'admin123',
        'new_password' => 'newpassword123',
        'confirm_password' => 'mismatched123',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $mismatchRes = $kernel->handle($mismatchReq);
    $t->assertEquals(422, $mismatchRes->getStatusCode());

    // 7. PUT /admin/profile - Valid update (name change)
    $originalName = $currentUser->name;
    $validUpdateReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Super Administrator Live',
        'email' => 'admin@syntaxcore.com',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $validUpdateRes = $kernel->handle($validUpdateReq);
    $t->assertEquals(200, $validUpdateRes->getStatusCode());
    $updateData = json_decode($validUpdateRes->getContent(), true);
    $t->assertEquals('success', $updateData['status'] ?? null);
    $t->assertEquals('Super Administrator Live', $updateData['user']['name'] ?? null);

    // Verify activity log was recorded
    $logCheck = \Core\Database\Connection::selectOne(
        "SELECT * FROM `activity_logs` WHERE `user_id` = ? AND `action` = 'user.profile_update' ORDER BY `id` DESC LIMIT 1",
        [$currentUser->id]
    );
    $t->assert($logCheck !== null, 'Profile update activity log must be generated');

    // Restore original name
    $restoreReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => $originalName,
        'email' => 'admin@syntaxcore.com',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => '/admin/profile',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $kernel->handle($restoreReq);

    $auth->logout();
});

$t->test('Desktop Wallpaper Upload & Customization Architecture', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);

    // 1. Guest request with valid CSRF to POST /admin/wallpaper returns 401 (blocked by auth middleware)
    $guestToken = \Core\Security\Csrf::token();
    $guestReq = new \Core\Http\Request([], [
        '_token' => $guestToken,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/wallpaper',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $guestRes = $kernel->handle($guestReq);
    $t->assertEquals(401, $guestRes->getStatusCode(), 'Guest request to POST /admin/wallpaper must return 401');

    // 2. Login as Superadmin
    $auth->attempt('admin@syntaxcore.com', 'admin123');
    $token = \Core\Security\Csrf::token();

    // 3. POST /admin/wallpaper without file -> 422
    $noFileReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/wallpaper',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $noFileRes = $kernel->handle($noFileReq);
    $t->assertEquals(422, $noFileRes->getStatusCode());

    // 4. POST /admin/wallpaper with invalid file format (.txt) -> 422
    $tmpTxt = tempnam(sys_get_temp_dir(), 'test_wp') . '.txt';
    file_put_contents($tmpTxt, 'dummy text content');
    $invalidFileReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/wallpaper',
        'HTTP_ACCEPT' => 'application/json',
    ], [], [
        'wallpaper' => [
            'name' => 'document.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpTxt,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpTxt),
        ]
    ]);
    $invalidFileRes = $kernel->handle($invalidFileReq);
    $t->assertEquals(422, $invalidFileRes->getStatusCode());
    @unlink($tmpTxt);

    // 5. POST /admin/wallpaper with valid PNG image -> 200
    $pngContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $tmpPng = tempnam(sys_get_temp_dir(), 'test_wp') . '.png';
    file_put_contents($tmpPng, $pngContent);

    $validFileReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/wallpaper',
        'HTTP_ACCEPT' => 'application/json',
    ], [], [
        'wallpaper' => [
            'name' => 'desktop_bg.png',
            'type' => 'image/png',
            'tmp_name' => $tmpPng,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmpPng),
        ]
    ]);
    $validFileRes = $kernel->handle($validFileReq);
    $t->assertEquals(200, $validFileRes->getStatusCode());
    $uploadData = json_decode($validFileRes->getContent(), true);
    $t->assertEquals('success', $uploadData['status'] ?? null);
    $t->assert(str_starts_with($uploadData['url'] ?? '', '/uploads/wallpapers/'), 'URL must point to /uploads/wallpapers/');

    $savedFilePath = $baseDir . '/public' . $uploadData['url'];
    $t->assert(file_exists($savedFilePath), 'Uploaded wallpaper file must exist in public/uploads/wallpapers/');

    // 6. DELETE /admin/wallpaper -> 200
    $delReq = new \Core\Http\Request([], [
        '_token' => $token,
    ], [
        'REQUEST_METHOD' => 'DELETE',
        'REQUEST_URI' => '/admin/wallpaper',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $delRes = $kernel->handle($delReq);
    $t->assertEquals(200, $delRes->getStatusCode());

    // Clean up temporary testing files
    @unlink($tmpPng);
    @unlink($savedFilePath);

    $auth->logout();
});

// ==========================================
// 12. CMS MODELS & SCHEMA INTEGRATION
// ==========================================
$t->suite('CMS Models & Schema Integration');

$t->test('CMS Categories, Tags, and News Relationship Operations', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';

    // 1. Test Category
    $cat = \App\Models\Category::findBySlug('pengumuman');
    $t->assert($cat !== null, 'Category pengumuman should exist in database');
    $t->assertEquals('Pengumuman', $cat->name);
    $t->assert(is_int($cat->newsCount()), 'newsCount() should return an integer');

    // 2. Test Tag
    $tag = \App\Models\Tag::firstOrCreateByName('CMS Test Tag');
    $t->assert($tag !== null, 'Tag should be created or retrieved');
    $t->assertEquals('cms-test-tag', $tag->slug);

    // 3. Test News
    $admin = \App\Models\User::findByEmail('admin@syntaxcore.com');
    $news = \App\Models\News::create([
        'category_id' => $cat->id,
        'user_id' => $admin?->id,
        'title' => 'Test News Item ' . time(),
        'slug' => 'test-news-item-' . time(),
        'summary' => 'Test summary',
        'content' => '<p>Test content body</p>',
        'status' => 'published',
        'published_at' => date('Y-m-d H:i:s'),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $t->assert(!empty($news->id), 'News should have an auto-increment ID');

    // Sync tags
    $news->syncTags([$tag->id]);
    $newsTags = $news->tags();
    $t->assertEquals(1, count($newsTags), 'News should have 1 tag attached');
    $t->assertEquals($tag->name, $newsTags[0]->name);

    // Test author and category relations
    $t->assertEquals($cat->id, $news->category()?->id);
    if ($admin) {
        $t->assertEquals($admin->id, $news->author()?->id);
    }

    // Test incrementViews
    $initialViews = (int) $news->views_count;
    $news->incrementViews();
    $t->assertEquals($initialViews + 1, (int) $news->views_count);

    // 4. Test Comments
    $comment = \App\Models\Comment::create([
        'news_id' => $news->id,
        'author_name' => 'Reviewer',
        'author_email' => 'reviewer@example.com',
        'content' => 'First comment on test article',
        'status' => 'approved',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $t->assert(!empty($comment->id), 'Comment should have an ID');

    $reply = \App\Models\Comment::create([
        'news_id' => $news->id,
        'parent_id' => $comment->id,
        'author_name' => 'Author Reply',
        'author_email' => 'author@example.com',
        'content' => 'Thanks for reading!',
        'status' => 'approved',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $t->assertEquals($comment->id, $reply->parent()?->id, 'Reply parent should match parent comment');
    $replies = $comment->replies();
    $t->assertEquals(1, count($replies), 'Comment should have 1 reply');

    $t->assertEquals(2, $news->approvedCommentsCount(), 'News should have 2 approved comments');

    // Cleanup test news (cascades to comments and news_tag)
    $news->delete();
    $tag->delete();
});

$t->test('CMS Pages and Custom Comment Settings Configuration', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';

    $page = \App\Models\Page::findBySlug('baca-berita');
    $t->assert($page !== null, 'Page baca-berita should exist');
    $t->assert($page->isNewsSingle(), 'baca-berita should be news_single');

    $settings = $page->getCommentSettings();
    $t->assert(isset($settings['enabled']), 'Comment settings must have enabled field');
    $t->assert(isset($settings['style']), 'Comment settings must have style field');

    // Test modification of comment settings
    $customPage = \App\Models\Page::create([
        'title' => 'Custom Magazine Reader ' . time(),
        'slug' => 'magazine-reader-' . time(),
        'page_type' => 'news_single',
        'status' => 'published',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    $customPage->setCommentSettings([
        'enabled' => true,
        'style' => 'threaded',
        'allow_guests' => false,
        'per_page' => 5,
    ]);
    $customPage->save();

    $reloaded = \App\Models\Page::find($customPage->id);
    $reloadedSettings = $reloaded->getCommentSettings();
    $t->assertEquals('threaded', $reloadedSettings['style']);
    $t->assertEquals(false, $reloadedSettings['allow_guests']);
    $t->assertEquals(5, $reloadedSettings['per_page']);

    $customPage->delete();
});

$t->test('Public Menus and Hierarchical Tree Navigation', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';

    $tree = \App\Models\PublicMenu::tree(true);
    $t->assert(is_array($tree), 'PublicMenu::tree() should return an array');
    $t->assert(count($tree) >= 3, 'Tree should have at least 3 root items (Beranda, Berita, Tentang Kami)');

    // Find Berita & Artikel in tree
    $newsMenu = null;
    foreach ($tree as $item) {
        if (str_contains($item['title'], 'Berita')) {
            $newsMenu = $item;
            break;
        }
    }

    $t->assert($newsMenu !== null, 'Berita root menu should be found in tree');
    $t->assert(isset($newsMenu['children']), 'Berita menu should have children array');
    $t->assert(count($newsMenu['children']) >= 2, 'Berita menu should have at least 2 submenus (Teknologi, Pengumuman)');
    $t->assertEquals('/berita/kategori/teknologi', $newsMenu['children'][0]['computed_url']);
});

$t->test('Admin CMS Controller Endpoints & RBAC Protection', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);
    $csrf = $app->make(\Core\Security\Csrf::class);
    $token = $csrf->token();

    // 1. Regular user gets 403 Forbidden on CMS endpoints
    $userRole = \App\Models\Role::findBySlug('user');
    $testEmail = 'regular_cms_tester_' . bin2hex(random_bytes(4)) . '@example.com';
    $normalUser = \App\Models\User::create([
        'name' => 'Regular Tester',
        'email' => $testEmail,
        'password' => password_hash('secret123', PASSWORD_BCRYPT),
        'role_id' => $userRole->id,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    $auth->login($normalUser);

    $forbiddenReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/cms/news',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $forbiddenRes = $kernel->handle($forbiddenReq);
    $t->assertEquals(403, $forbiddenRes->getStatusCode(), 'User role should get 403 on /admin/cms/news');
    $auth->logout();

    // 2. Admin logs in and performs CMS operations
    $admin = \App\Models\User::findByEmail('admin@syntaxcore.com');
    $t->assert($admin !== null, 'Admin user must exist');
    $auth->login($admin);
    $token = $csrf->token();

    // 2a. GET /admin/cms/categories -> 200
    $catReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/cms/categories',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $catRes = $kernel->handle($catReq);
    $t->assertEquals(200, $catRes->getStatusCode());
    $catData = json_decode($catRes->getContent(), true);
    $t->assertEquals('success', $catData['status'] ?? null);

    // 2b. POST /admin/cms/categories -> 201
    $newCatReq = new \Core\Http\Request([], [
        '_token' => $token,
        'name' => 'Kategori Uji ' . time(),
        'color' => '#dc3545',
        'description' => 'Kategori untuk unit testing',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/cms/categories',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $newCatRes = $kernel->handle($newCatReq);
    $t->assertEquals(201, $newCatRes->getStatusCode());
    $newCatData = json_decode($newCatRes->getContent(), true);
    $createdCatId = $newCatData['data']['id'] ?? null;
    $t->assert(!empty($createdCatId), 'Created category must return ID');

    // 2c. POST /admin/cms/pages -> 201
    $pageReq = new \Core\Http\Request([], [
        '_token' => $token,
        'title' => 'Halaman Uji ' . time(),
        'slug' => 'halaman-uji-' . time(),
        'page_type' => 'standard',
        'content' => '<p>Konten pengujian otomatis</p>',
        'status' => 'published',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/cms/pages',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $pageRes = $kernel->handle($pageReq);
    $t->assertEquals(201, $pageRes->getStatusCode());
    $pageData = json_decode($pageRes->getContent(), true);
    $createdPageId = $pageData['data']['id'] ?? null;
    $t->assert(!empty($createdPageId), 'Created page must return ID');

    // 2d. POST /admin/cms/news -> 201
    $newsReq = new \Core\Http\Request([], [
        '_token' => $token,
        'title' => 'Berita Uji Integrasi ' . time(),
        'slug' => 'berita-uji-' . time(),
        'category_id' => $createdCatId,
        'summary' => 'Ringkasan berita uji',
        'content' => '<p>Konten lengkap berita uji</p>',
        'status' => 'published',
        'tags' => 'Testing, API, PHP',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/cms/news',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $newsRes = $kernel->handle($newsReq);
    $t->assertEquals(201, $newsRes->getStatusCode());
    $newsData = json_decode($newsRes->getContent(), true);
    $createdNewsId = $newsData['data']['id'] ?? null;
    $t->assert(!empty($createdNewsId), 'Created news must return ID');

    // 2e. GET /admin/cms/news/{id} -> 200
    $showNewsReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/cms/news/' . $createdNewsId,
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $showNewsRes = $kernel->handle($showNewsReq);
    $t->assertEquals(200, $showNewsRes->getStatusCode());

    // 2f. GET /admin/cms/menus -> 200
    $menusReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/admin/cms/menus',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $menusRes = $kernel->handle($menusReq);
    $t->assertEquals(200, $menusRes->getStatusCode());

    // Cleanup
    \App\Models\News::find($createdNewsId)?->delete();
    \App\Models\Page::find($createdPageId)?->delete();
    \App\Models\Category::find($createdCatId)?->delete();
    $normalUser->delete();

    $auth->logout();
});

$t->test('Public CMS Engine Routing, Dynamic Pages, & News Reader', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // 1. GET / (Home) -> 200 with dynamic navbar and content
    $homeReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/',
    ]);
    $homeRes = $kernel->handle($homeReq);
    $t->assertEquals(200, $homeRes->getStatusCode());
    $t->assertContains('SyntaxCore', $homeRes->getContent());
    $t->assertContains('Warta & Berita', $homeRes->getContent());

    // 2. GET /tentang-kami (Standard Custom Page) -> 200
    $aboutReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/tentang-kami',
    ]);
    $aboutRes = $kernel->handle($aboutReq);
    $t->assertEquals(200, $aboutRes->getStatusCode());
    $t->assertContains('Tentang Kami', $aboutRes->getContent());

    // 3. GET /berita (News Index Feed) -> 200
    $newsIndexReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/berita',
    ]);
    $newsIndexRes = $kernel->handle($newsIndexReq);
    $t->assertEquals(200, $newsIndexRes->getStatusCode());
    $t->assertContains('Kategori:', $newsIndexRes->getContent());

    // 4. GET /berita/kategori/teknologi -> 200
    $catReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/berita/kategori/teknologi',
    ]);
    $catRes = $kernel->handle($catReq);
    $t->assertEquals(200, $catRes->getStatusCode());
    $t->assertContains('Teknologi', $catRes->getContent());

    // 5. GET /berita/{slug} (News Single Reader) -> 200 & increments views
    $news = \App\Models\News::getPublishedNews(limit: 1)[0] ?? null;
    $t->assert($news !== null, 'At least one published news must exist');
    $initialViews = (int) $news->views_count;

    $singleReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/berita/' . $news->slug,
    ]);
    $singleRes = $kernel->handle($singleReq);
    $t->assertEquals(200, $singleRes->getStatusCode());
    $t->assertContains($news->title, $singleRes->getContent());
    $t->assertContains('Diskusi & Komentar', $singleRes->getContent());

    $refreshedNews = \App\Models\News::find($news->id);
    $t->assertEquals($initialViews + 1, (int) $refreshedNews->views_count, 'Views count must increment upon viewing article');

    // 6. Non-existent slug -> 404
    $notFoundReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/halaman-fiktif-tidak-ada-999',
    ]);
    $notFoundRes = $kernel->handle($notFoundReq);
    $t->assertEquals(404, $notFoundRes->getStatusCode());
});

$t->test('Public Comment Submission, Honeypot Anti-Spam, and XSS Sanitization', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $csrf = $app->make(\Core\Security\Csrf::class);
    $token = $csrf->token();

    $news = \App\Models\News::getPublishedNews(limit: 1)[0] ?? null;
    $t->assert($news !== null, 'News item must exist for comment testing');

    // 1. POST without CSRF token -> 419
    $noCsrfReq = new \Core\Http\Request([], [
        'author_name' => 'Spammer',
        'author_email' => 'spam@test.com',
        'content' => 'Spam comment without token',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/news/{$news->id}/comments",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $noCsrfRes = $kernel->handle($noCsrfReq);
    $t->assertEquals(419, $noCsrfRes->getStatusCode(), 'POST without CSRF must return 419');

    // 2. POST with honeypot spam filled -> 422 rejected
    $honeypotReq = new \Core\Http\Request([], [
        '_token' => $token,
        'website_hp' => 'http://spam-bot-link.com',
        'author_name' => 'Bot Spammer',
        'author_email' => 'bot@spam.com',
        'content' => 'Spam comment with bot',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/news/{$news->id}/comments",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $honeypotRes = $kernel->handle($honeypotReq);
    $t->assertEquals(422, $honeypotRes->getStatusCode(), 'Honeypot trap must reject spam request');

    // 3. POST with valid data and XSS payload -> 201 created & sanitized
    $xssPayload = '<script>alert("XSS")</script> Komentar sah dengan <b>format</b>.';
    $validReq = new \Core\Http\Request([], [
        '_token' => $token,
        'website_hp' => '',
        'author_name' => 'Budi Santoso',
        'author_email' => 'budi@example.com',
        'content' => $xssPayload,
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/news/{$news->id}/comments",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $validRes = $kernel->handle($validReq);
    $t->assertEquals(201, $validRes->getStatusCode(), 'Valid comment submission should return 201');

    $commentData = json_decode($validRes->getContent(), true);
    $createdCommentId = $commentData['data']['id'] ?? null;
    $t->assert(!empty($createdCommentId), 'Created comment ID must be returned');

    $savedComment = \App\Models\Comment::find($createdCommentId);
    $t->assert($savedComment !== null, 'Comment must be persisted to database');
    $t->assert(!str_contains($savedComment->content, '<script>'), 'Script tags must be sanitized via htmlspecialchars');
    $t->assertContains('&lt;script&gt;', $savedComment->content, 'Raw HTML must be escaped safely');

    // Clean up
    $savedComment->delete();
});

$t->test('Comment Moderation Cycle and Admin Direct Reply', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);
    $auth = $app->make(\App\Services\AuthService::class);
    $csrf = $app->make(\Core\Security\Csrf::class);
    $token = $csrf->token();

    $news = \App\Models\News::getPublishedNews(limit: 1)[0] ?? null;
    $t->assert($news !== null);

    // Create a pending comment
    $pendingComment = \App\Models\Comment::create([
        'news_id' => $news->id,
        'author_name' => 'User Tertunda',
        'author_email' => 'tertunda@example.com',
        'content' => 'Pertanyaan yang membutuhkan moderasi',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    // Admin logs in
    $admin = \App\Models\User::findByEmail('admin@syntaxcore.com');
    $auth->login($admin);
    $adminToken = $csrf->token();

    // 1. Admin approves the comment -> PUT /admin/cms/comments/{id}/status
    $approveReq = new \Core\Http\Request([], [
        '_token' => $adminToken,
        'status' => 'approved',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => "/admin/cms/comments/{$pendingComment->id}/status",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $approveRes = $kernel->handle($approveReq);
    $t->assertEquals(200, $approveRes->getStatusCode());

    $updatedComment = \App\Models\Comment::find($pendingComment->id);
    $t->assertEquals('approved', $updatedComment->status);

    // 2. Admin replies to the comment -> POST /admin/cms/comments/{id}/reply
    $replyReq = new \Core\Http\Request([], [
        '_token' => $adminToken,
        'content' => 'Terima kasih atas pertanyaannya! Jawaban kami telah dikirim.',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => "/admin/cms/comments/{$pendingComment->id}/reply",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $replyRes = $kernel->handle($replyReq);
    $t->assertEquals(201, $replyRes->getStatusCode());
    $replyData = json_decode($replyRes->getContent(), true);
    $replyCommentId = $replyData['data']['id'] ?? null;
    $t->assert(!empty($replyCommentId));

    $replyComment = \App\Models\Comment::find($replyCommentId);
    $t->assertEquals($pendingComment->id, $replyComment->parent_id);
    $t->assertEquals('approved', $replyComment->status);
    $t->assertEquals($admin->id, $replyComment->user_id);

    // Clean up
    $replyComment->delete();
    $pendingComment->delete();
    $auth->logout();
});

$t->test('Page Layout Templates (Full-Width, Sidebar, Blank, Default) & Blueprint Resolution', function ($t) use ($baseDir) {
    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $auth = $app->make(\App\Services\AuthService::class);
    $csrf = $app->make(\Core\Security\Csrf::class);
    $admin = \App\Models\User::findByEmail('admin@syntaxcore.com');
    $auth->login($admin);
    $adminToken = $csrf->token();

    // 1. Create page with 'fullwidth' layout via admin API
    $createReq = new \Core\Http\Request([], [
        '_token' => $adminToken,
        'title' => 'Landing Showcase Test',
        'slug' => 'landing-showcase-test',
        'page_type' => 'standard',
        'layout_template' => 'fullwidth',
        'content' => '<div class="hero-showcase">Banner Penuh Konten</div>',
        'status' => 'published',
    ], [
        'REQUEST_METHOD' => 'POST',
        'REQUEST_URI' => '/admin/cms/pages',
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $createRes = $kernel->handle($createReq);
    $t->assertEquals(201, $createRes->getStatusCode());
    $createdData = json_decode($createRes->getContent(), true)['data'] ?? [];
    $pageId = (int) ($createdData['id'] ?? 0);
    $t->assert($pageId > 0);

    $page = \App\Models\Page::find($pageId);
    $t->assertEquals('fullwidth', $page->getLayoutTemplate());

    // 2. Access public GET /landing-showcase-test -> should render fullwidth view
    $pubReq = new \Core\Http\Request([], [], [
        'REQUEST_METHOD' => 'GET',
        'REQUEST_URI' => '/landing-showcase-test',
    ]);
    $pubRes = $kernel->handle($pubReq);
    $t->assertEquals(200, $pubRes->getStatusCode());
    $t->assertContains('Banner Penuh Konten', $pubRes->getContent());
    $t->assertContains('w-100 flex-grow-1', $pubRes->getContent(), 'Fullwidth view must have w-100 container');

    // 3. Update to 'sidebar' layout
    $updateReq = new \Core\Http\Request([], [
        '_token' => $adminToken,
        'title' => 'Documentation Sidebar Test',
        'layout_template' => 'sidebar',
        'content' => '<p>Konten dokumentasi dengan navigasi samping</p>',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => "/admin/cms/pages/{$pageId}",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $updateRes = $kernel->handle($updateReq);
    $t->assertEquals(200, $updateRes->getStatusCode());

    // 4. Access public GET /landing-showcase-test -> should render sidebar view
    $sideRes = $kernel->handle($pubReq);
    $t->assertEquals(200, $sideRes->getStatusCode());
    $t->assertContains('Halaman Terkait', $sideRes->getContent(), 'Sidebar view must include Halaman Terkait widget');
    $t->assertContains('Konten dokumentasi dengan navigasi samping', $sideRes->getContent());

    // 5. Update to 'blank' layout
    $blankUpdateReq = new \Core\Http\Request([], [
        '_token' => $adminToken,
        'layout_template' => 'blank',
    ], [
        'REQUEST_METHOD' => 'PUT',
        'REQUEST_URI' => "/admin/cms/pages/{$pageId}",
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $blankUpdateRes = $kernel->handle($blankUpdateReq);
    $t->assertEquals(200, $blankUpdateRes->getStatusCode());

    // 6. Access public GET /landing-showcase-test -> should render blank view
    $blankRes = $kernel->handle($pubReq);
    $t->assertEquals(200, $blankRes->getStatusCode());
    $t->assertContains('Minimalist Clean Main Content', $blankRes->getContent());

    // Clean up
    $page->delete();
    $auth->logout();
});

// Print final summary
exit($t->summary());


