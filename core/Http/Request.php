<?php

namespace Core\Http;

class Request
{
    protected string $method;
    protected string $uri;
    protected string $path;
    protected array $query;
    protected array $post;
    protected array $server;
    protected array $headers;
    protected array $cookies;
    protected array $files;
    protected array $params = [];
    protected ?string $rawBody = null;

    public function __construct(
        array $query = [],
        array $post = [],
        array $server = [],
        array $cookies = [],
        array $files = [],
        ?string $rawBody = null
    ) {
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->rawBody = $rawBody;

        $this->method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        $this->uri = $this->server['REQUEST_URI'] ?? '/';
        $this->path = parse_url($this->uri, PHP_URL_PATH) ?: '/';
        $this->headers = $this->extractHeaders();

        // Support JSON request body parsing
        if ($this->isJson() && !empty($this->rawBody)) {
            $jsonData = json_decode($this->rawBody, true);
            if (is_array($jsonData)) {
                $this->post = array_merge($this->post, $jsonData);
            }
        }
    }

    /**
     * Capture the current HTTP request from PHP superglobals.
     */
    public static function capture(): static
    {
        return new static(
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
            $_FILES,
            file_get_contents('php://input') ?: null
        );
    }

    protected function extractHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($key))));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function getMethod(): string
    {
        return $this->method();
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = $this->all();
        if (is_null($key)) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->params);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        foreach ($this->headers as $header => $value) {
            if (strcasecmp($header, $key) === 0) {
                return $value;
            }
        }
        return $default;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, '/json') || str_contains($contentType, '+json');
    }

    public function wantsJson(): bool
    {
        $accept = $this->header('Accept', '');
        return str_contains($accept, '/json') || str_contains($accept, '+json');
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }
}
