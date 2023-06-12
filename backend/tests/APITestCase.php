<?php

namespace Tests;

use Shipyard\App;
use Shipyard\Traits\CreatesUniqueIDs;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\UploadedFile;
use Slim\Psr7\Uri;
use SlimSession\Helper as SessionHelper;

ob_start();

class APITestCase extends TestCase {
    use CreatesUniqueIDs;

    /** @var int|null */
    protected $statusCode = null;
    /** @var \Psr\Http\Message\ResponseInterface|null */
    protected $response = null;
    /** @var \Slim\App|null */
    protected $app = null;

    public function setUp(): void {
        parent::setUp();
        $this->app = (new App())->get();
        session_start();
    }

    public function tearDown(): void {
        $session = new SessionHelper();
        $session::destroy();
        parent::tearDown();
    }

    /**
     * @param array<string,string>                                                                      $headers
     * @param array<string,string>                                                                      $cookies
     * @param array<string,string>                                                                      $serverParams
     * @param array<string, array<int, \Slim\Psr7\UploadedFile>>|array<string, \Slim\Psr7\UploadedFile> $uploadedFiles
     *
     * @return SlimRequest
     */
    protected function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $cookies = [],
        array $serverParams = [],
        array $uploadedFiles = []
    ) {
        $path = $_SERVER['BASE_URL'] . '/' . $path;
        $uri = new Uri('', '', 80, $path);
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            throw new \ErrorException('Unable to open file handle.');
        }
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }

        return new SlimRequest($method, $uri, $h, $cookies, $serverParams, $stream, $uploadedFiles);
    }

    /**
     * @param string $name
     *
     * @return \Slim\Psr7\UploadedFile
     */
    protected function createSampleUpload($name = 'science-vessel.ship') {
        $filepath = (string) realpath('tests/assets/' . $name);
        $temppath = (string) tempnam(sys_get_temp_dir(), 'TLS');
        copy($filepath, $temppath);

        return new UploadedFile($temppath, $name, 'application/octet-stream', 1952654);
    }

    /**
     * @param string               $route
     * @param array<string,string> $headers
     *
     * @return $this
     */
    public function get($route, $headers) {
        $this->response = null;
        $this->statusCode = null;
        $request = $this->createRequest('GET', $route, $headers);
        $this->response = $this->app->handle($request);
        $this->statusCode = $this->response->getStatusCode();

        return $this;
    }

    /**
     * @param string                                                                                    $route
     * @param array<string, array<string>|string>                                                       $args
     * @param array<string, string>                                                                     $headers
     * @param array<string, array<int, \Slim\Psr7\UploadedFile>>|array<string, \Slim\Psr7\UploadedFile> $uploadedFiles
     *
     * @return $this
     */
    public function post($route, $args, $headers, $uploadedFiles = []) {
        $this->response = null;
        $this->statusCode = null;
        $request = $this->createRequest('POST', $route, $headers, [], [], $uploadedFiles)->withParsedBody($args);
        $this->response = $this->app->handle($request);
        $this->statusCode = $this->response->getStatusCode();

        return $this;
    }

    /**
     * @param string                              $route
     * @param array<string, array<string>|string> $args
     * @param array<string, string>               $headers
     *
     * @return $this
     */
    public function put($route, $args, $headers) {
        $this->response = null;
        $this->statusCode = null;
        $request = $this->createRequest('PUT', $route, $headers)->withParsedBody($args);
        $this->response = $this->app->handle($request);
        $this->statusCode = $this->response->getStatusCode();

        return $this;
    }

    /**
     * @param string               $route
     * @param array<string,string> $headers
     *
     * @return $this
     */
    public function delete($route, $headers) {
        $this->response = null;
        $this->statusCode = null;
        $request = $this->createRequest('DELETE', $route, $headers);
        $this->response = $this->app->handle($request);
        $this->statusCode = $this->response->getStatusCode();

        return $this;
    }

    /**
     * @param int $code
     *
     * @return $this
     */
    public function assertStatus($code) {
        $this->assertEquals($code, $this->statusCode);

        return $this;
    }

    /**
     * @param mixed[] $dataExpected
     * @param bool    $negate
     *
     * @return $this
     */
    public function assertJsonResponse($dataExpected, $negate = false) {
        $dataActual = json_decode((string) $this->response->getBody(), true);

        return $this->assertJsonFragment($dataExpected, $dataActual, $negate);
    }
}
