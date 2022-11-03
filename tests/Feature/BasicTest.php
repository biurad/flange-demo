<?php declare(strict_types=1);

/*
 * This file is part of RadePHP Demo Project
 *
 * @copyright 2022 Divine Niiquaye Ibok (https://divinenii.com/)
 * @license   https://opensource.org/licenses/MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Feature;

use App\Tests\TestCase;
use App\Tests\Traits\InteractsWithHttp;
use Psr\Http\Message\ResponseInterface;

class BasicTest extends TestCase
{
    use InteractsWithHttp;

    /**
     * PHPUnit's data providers allow to execute the same tests repeated times
     * using a different set of data each time.
     *
     * @dataProvider getPublicUrls
     * @runInSeparateProcess
     */
    public function testRoutingActionWorks(string $uri, int $statusCode, string $path): void
    {
        $response = $this->makeApp()->handle($this->request($uri));

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertEquals($statusCode, $response->getStatusCode());
        $this->assertEquals($path, $response->getHeaderLine('Location'));
    }

    public function testRoutingWithLogin(): void
    {
        $app = $this->makeApp();
        $response = $app->handle($this->request('/fr/login/', 'POST')->withParsedBody([
            '_identifier' => 'jane_admin',
            '_password' => 'kitten'
        ]));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/fr/blog/', $response->getHeaderLine('Location'));
    }

    public function getPublicUrls(): \Generator
    {
        yield ['/en/', 200, ''];

        yield ['/en/blog/', 200, ''];

        yield ['/en/profile/edit', 302, '/login/'];

        yield ['/admin/post/', 302, '/login/'];
    }
}
