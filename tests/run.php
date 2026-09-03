<?php

/**
 * SyntaxCore Architecture & Integration Test Runner
 * Zero-dependency, lightweight, native test suite.
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
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

$t->test('Fillable protects mass-assignment', function ($t) {
    $userModel = new class(['name' => 'John', 'role' => 'admin']) extends \Core\Database\Model {
        protected array $fillable = ['name'];
    };

    $attrs = $userModel->toArray();
    $t->assertEquals('John', $attrs['name'] ?? null);
    $t->assert(!isset($attrs['role']), 'Unfillable attribute role must be filtered out');
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
// 7. ADMIN AUTHENTICATION MODULE
// ==========================================
$t->suite('Admin Authentication Module');

$t->test('Admin User model creation and password hashing', function ($t) {
    // Delete existing test user if any
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
    $auth->logout(); // Ensure guest

    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin']);
    $res = $kernel->handle($req);

    $t->assertEquals(302, $res->getStatusCode());
    $t->assertEquals('/admin/login', $res->getHeaders()['Location'] ?? null);
});

$t->test('Guest can access /admin/login and view Bootstrap login form', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->logout();

    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/login']);
    $res = $kernel->handle($req);

    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('Admin Login', $res->getContent());
    $t->assertContains('type="email"', $res->getContent());
    $t->assertContains('type="password"', $res->getContent());
});

$t->test('POST /admin/login handles valid and invalid submissions', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->logout();

    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // Invalid login attempt
    $invalidReq = new \Core\Http\Request([], [
        'email' => 'admin@syntaxcore.test',
        'password' => 'wrongpassword',
    ], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/login']);
    $invalidRes = $kernel->handle($invalidReq);

    $t->assertEquals(422, $invalidRes->getStatusCode());
    $t->assertContains('Invalid credentials.', $invalidRes->getContent());

    // Valid login attempt
    $validReq = new \Core\Http\Request([], [
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

    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // Access protected /admin
    $req = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin']);
    $res = $kernel->handle($req);

    $t->assertEquals(200, $res->getStatusCode());
    $t->assertContains('Admin Dashboard', $res->getContent());
    $t->assertContains('Admin Test', $res->getContent());

    // Attempt to access guest-only /admin/login
    $loginReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/admin/login']);
    $loginRes = $kernel->handle($loginReq);

    $t->assertEquals(302, $loginRes->getStatusCode());
    $t->assertEquals('/admin', $loginRes->getHeaders()['Location'] ?? null);
});

$t->test('Logout clears session and subsequent protected requests redirect to login', function ($t) use ($baseDir) {
    $auth = new \App\Services\AuthService();
    $auth->attempt('admin@syntaxcore.test', 'secretpassword123');

    /** @var \Core\Application\Application $app */
    $app = require $baseDir . '/bootstrap/app.php';
    $kernel = $app->make(\Core\Application\Kernel::class);

    // POST /admin/logout
    $logoutReq = new \Core\Http\Request([], [], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/admin/logout']);
    $logoutRes = $kernel->handle($logoutReq);

    $t->assertEquals(302, $logoutRes->getStatusCode());
    $t->assertEquals('/admin/login', $logoutRes->getHeaders()['Location'] ?? null);
    $t->assert($auth->guest(), 'User must now be guest');

    // Subsequent access to /admin redirects to login
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

// Print final summary
exit($t->summary());
