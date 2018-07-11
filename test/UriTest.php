<?php

declare(strict_types=1);

namespace Test\Aidphp\Http;

use PHPUnit\Framework\TestCase;
use Aidphp\Http\Uri;
use TypeError;
use InvalidArgumentException;

class UriTest extends TestCase
{
    public function testDefaultUri()
    {
        $uri = new Uri();

        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getAuthority());
        $this->assertSame('', $uri->getUserInfo());
        $this->assertSame('', $uri->getHost());
        $this->assertNull($uri->getPort());
        $this->assertSame('', $uri->getPath());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
    }

    public function testParsesProvidedUri()
    {
        $uri = new Uri('https://user:pass@example.com:8080/path/123?q=abc#test');
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pass@example.com:8080', $uri->getAuthority());
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/path/123', $uri->getPath());
        $this->assertSame('q=abc', $uri->getQuery());
        $this->assertSame('test', $uri->getFragment());
        $this->assertSame('https://user:pass@example.com:8080/path/123?q=abc#test', (string) $uri);
    }

    /**
     * @dataProvider getValidUris
     */
    public function testValidUriStayValid($input)
    {
        $uri = new Uri($input);
        $this->assertSame($input, (string) $uri);
    }

    public function getValidUris()
    {
        return [
            // only path
            ['/'],
            ['relative/'],
            ['0'],
            // same document reference
            [''],
            // network path without scheme
            ['//example.org'],
            ['//example.org/'],
            ['//example.org?q#h'],
            // only query
            ['?q'],
            ['?q=abc&foo=bar'],
            // only fragment
            ['#fragment'],
            // dot segments are not removed automatically
            ['./foo/../bar'],
            ['../../book/catalog.xml'],
            // utf8
            ['http://ουτοπία.δπθ.gr/'],
            // multiple test
            ['http://foo.com/blah_blah'],
            ['http://foo.com/blah_blah/'],
            ['http://foo.com/blah_blah_(wikipedia)'],
            ['http://foo.com/blah_blah_(wikipedia)_(again)'],
            ['http://www.example.com/wpstyle/?p=364'],
            ['https://www.example.com/foo/?bar=baz&inga=42&quux'],
            ['http://✪df.ws/123'],
            ['http://userid:password@example.com:8080'],
            ['http://userid:password@example.com:8080/'],
            ['http://userid@example.com'],
            ['http://userid@example.com/'],
            ['http://userid@example.com:8080'],
            ['http://userid@example.com:8080/'],
            ['http://userid:password@example.com'],
            ['http://userid:password@example.com/'],
            ['http://142.42.1.1/'],
            ['http://142.42.1.1:8080/'],
            ['http://➡.ws/䨹'],
            ['http://⌘.ws'],
            ['http://⌘.ws/'],
            ['http://foo.com/blah_(wikipedia)#cite-1'],
            ['http://foo.com/blah_(wikipedia)_blah#cite-1'],
            ['http://foo.com/(something)?after=parens'],
            ['http://☺.exemple.com/'],
            ['http://code.google.com/events/#&product=browser'],
            ['http://j.mp'],
            ['http://foo.bar/?q=Test%20URL-encoded%20stuff'],
            ['http://例子.测试'],
            ['http://उदाहरण.परीक्षा'],
            ['http://-.~_!$&()*+,;=:%40:80%2f::::::@example.com'],
            ['http://1337.net'],
            ['http://a.b-c.de'],
            // ipv4
            ['http://192.168.1.1'],
            // ipv6
            ['http://[::1]:8000/api/'],
        ];
    }

    /**
     * @dataProvider getInvalidUris
     */
    public function testInvalidUriThrowException($invalidUri)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse URI');
        new Uri($invalidUri);
    }

    public function getInvalidUris()
    {
        return [
            ['http://'],
            ['http://?'],
            ['http://??'],
            ['http://??/'],
            ['http://#'],
            ['http://##'],
            ['http://##/'],
            ['//'],
            ['///a'],
            ['///'],
            ['http:///a'],
            ['http://:80'],
            ['http://user@:80'],
            ['http://host:with:colon'],
        ];
    }

    /*** SCHEME ***/
    public function testSchemeIsNormalizedToLowercase()
    {
        $uri = new Uri('HTTP://example.com');
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('http://example.com', (string) $uri);

        $uri = (new Uri('//example.com'))->withScheme('HTTP');
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('http://example.com', (string) $uri);
    }

    public function testNewInstanceWithNewScheme()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('http');
        $this->assertNotSame($uri, $new);
        $this->assertSame('http', $new->getScheme());
        $this->assertSame('http://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testSameInstanceWithSameScheme()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withScheme('https');
        $this->assertSame($uri, $new);
        $this->assertSame('https', $new->getScheme());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @dataProvider invalidSchemes
     */
    public function testInvalidScheme($scheme)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP scheme "' . $scheme . '" provided');
        new Uri($scheme . '://example.com');
    }

    public function invalidSchemes()
    {
        return [
            ['mailto'],
            ['ftp'],
            ['telnet'],
            ['ssh'],
            ['git'],
        ];
    }

    /**
     * @dataProvider getInvalidSchemeArguments
     */
    public function testWithSchemeInvalidArguments($scheme)
    {
        $this->expectException(TypeError::class);
        (new Uri())->withScheme($scheme);
    }

    public function getInvalidSchemeArguments()
    {
        return [
            [true],
            [['foobar']],
            [34],
            [new \stdClass()],
        ];
    }

    /*** AUTHORITY ***/
    /**
     * @dataProvider getAuthority
     */
    public function testAuthority($url, $authority)
    {
        $uri = new Uri($url);
        $this->assertEquals($authority, $uri->getAuthority());
    }

    public function getAuthority()
    {
        return [
            ['http://bar.com:80/', 'bar.com'],
            ['http://foo@bar.com:80/', 'foo@bar.com'],
            ['http://foo@bar.com:81/', 'foo@bar.com:81'],
            ['http://user:foo@bar.com/', 'user:foo@bar.com'],
            ['http://user:foo@bar.com:81/', 'user:foo@bar.com:81'],
        ];
    }

    /*** USERINFO ***/
    public function testNewInstanceWithUserAndNoPassword()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('aid');
        $this->assertNotSame($uri, $new);
        $this->assertSame('aid', $new->getUserInfo());
        $this->assertSame('https://aid@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testNewInstanceWithUserAndPassword()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('aid', 'php');
        $this->assertNotSame($uri, $new);
        $this->assertSame('aid:php', $new->getUserInfo());
        $this->assertSame('https://aid:php@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testSameInstanceWithSameUserAndPassword()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withUserInfo('user', 'pass');
        $this->assertSame($uri, $new);
        $this->assertSame('user:pass', $new->getUserInfo());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testAuthorityWithUserInfoButWithoutHost()
    {
        $uri = (new Uri())->withUserInfo('user', 'pass');
        $this->assertSame('user:pass', $uri->getUserInfo());
        $this->assertSame('', $uri->getAuthority());
    }

    /*** HOST ***/
    public function testNewInstanceWithNewHost()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('http://aidphp.com');
        $this->assertNotSame($uri, $new);
        $this->assertSame('http://aidphp.com', $new->getHost());
        $this->assertSame('https://user:pass@http://aidphp.com:3001/foo?bar=baz#quz', (string) $new);
    }
    public function testSameInstanceWithSameHost()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withHost('example.com');
        $this->assertSame($uri, $new);
        $this->assertSame('example.com', $new->getHost());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);

        $new = $uri->withHost('eXaMpLe.CoM');
        $this->assertSame($uri, $new);
        $this->assertSame('example.com', $new->getHost());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testHostIsNormalizedToLowercase()
    {
        $uri = new Uri('//eXaMpLe.CoM');
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('//example.com', (string) $uri);

        $uri = (new Uri('//aidphp.com'))->withHost('eXaMpLe.CoM');
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame('//example.com', (string) $uri);
    }

    /*** PORT ***/
    public function testPortMustBeValid()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP port "100000". Must be between 1 and 65535');
        (new Uri())->withPort(100000);
    }

    public function testWithPortCannotBeZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid HTTP port "0". Must be between 1 and 65535');
        (new Uri())->withPort(0);
    }

    public function testParseUriPortCannotBeZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to parse URI');
        new Uri('//example.com:0');
    }

    public function testPortCanBeRemoved()
    {
        $uri = (new Uri('http://example.com:8080'))->withPort(null);
        $this->assertNull($uri->getPort());
        $this->assertSame('http://example.com', (string) $uri);
    }

    public function testPortIsNullIfStandardPortForScheme()
    {
        // HTTPS standard port
        $uri = new Uri('https://example.com:443');
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());
        $uri = (new Uri('https://example.com'))->withPort(443);
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());

        // HTTP standard port
        $uri = new Uri('http://example.com:80');
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());
        $uri = (new Uri('http://example.com'))->withPort(80);
        $this->assertNull($uri->getPort());
        $this->assertSame('example.com', $uri->getAuthority());
    }

    public function testPortIsReturnedIfSchemeUnknown()
    {
        $uri = (new Uri('//example.com'))->withPort(80);
        $this->assertSame(80, $uri->getPort());
        $this->assertSame('example.com:80', $uri->getAuthority());
    }

    public function testStandardPortIsNullIfSchemeChanges()
    {
        $uri = new Uri('http://example.com:443');
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame(443, $uri->getPort());
        $uri = $uri->withScheme('https');
        $this->assertNull($uri->getPort());
    }

    /*** PATH ***/
    public function testNewInstanceWithNewPath()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/bar/baz');
        $this->assertNotSame($uri, $new);
        $this->assertSame('/bar/baz', $new->getPath());
        $this->assertSame('https://user:pass@example.com:3001/bar/baz?bar=baz#quz', (string) $new);
    }

    public function testSameInstanceWithSamePath()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withPath('/foo');
        $this->assertSame($uri, $new);
        $this->assertSame('/foo', $new->getPath());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @dataProvider getPaths
     */
    public function testPath($url, $path)
    {
        $uri = new Uri($url);
        $this->assertEquals($path, $uri->getPath());
    }

    public function getPaths()
    {
        return [
            ['http://www.foo.com/', '/'],
            ['http://www.foo.com', ''],
            ['foo/bar', 'foo/bar'],
            ['http://www.foo.com/foo bar', '/foo%20bar'],
            ['http://www.foo.com/foo%20bar', '/foo%20bar'],
            ['http://www.foo.com/foo%2fbar', '/foo%2fbar'],
            ['http://example.com/тестовый_путь/', '/тестовый_путь/'],
            ['http://example.com/ουτοπία/', '/ουτοπία/'],
        ];
    }

    /*** QUERY ***/
    public function testNewInstanceWithNewQuery()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withQuery('baz=bat');
        $this->assertNotSame($uri, $new);
        $this->assertSame('baz=bat', $new->getQuery());
        $this->assertSame('https://user:pass@example.com:3001/foo?baz=bat#quz', (string) $new);
    }

    public function testSameInstanceWithSameQuery()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withQuery('bar=baz');
        $this->assertSame($uri, $new);
        $this->assertSame('bar=baz', $new->getQuery());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    /**
     * @dataProvider getQueries
     */
    public function testQueryIsProperlyEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($query);
        $this->assertSame($expected, $uri->getQuery());
    }

    /**
     * @dataProvider getQueries
     */
    public function testQueryIsNotDoubleEncoded($query, $expected)
    {
        $uri = (new Uri())->withQuery($expected);
        $this->assertSame($expected, $uri->getQuery());
    }

    public function getQueries()
    {
        return [
            ['k^ey', 'k%5Eey'],
            ['k^ey=valu`', 'k%5Eey=valu%60'],
            ['key[]', 'key%5B%5D'],
            ['key[]=valu`', 'key%5B%5D=valu%60'],
            ['k^ey&key[]=valu`&f<>=`bar', 'k%5Eey&key%5B%5D=valu%60&f%3C%3E=%60bar'],
            ['q=тестовый_путь', 'q=тестовый_путь'],
            ['q=ουτοπία', 'q=ουτοπία'],
        ];
    }

    /*** FRAGMENT ***/
    public function testNewInstanceWithNewFragment()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('qat');
        $this->assertNotSame($uri, $new);
        $this->assertSame('qat', $new->getFragment());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#qat', (string) $new);
    }
    public function testSameInstanceWithSameFragment()
    {
        $uri = new Uri('https://user:pass@example.com:3001/foo?bar=baz#quz');
        $new = $uri->withFragment('quz');
        $this->assertSame($uri, $new);
        $this->assertSame('quz', $new->getFragment());
        $this->assertSame('https://user:pass@example.com:3001/foo?bar=baz#quz', (string) $new);
    }

    public function testFragmentIsProperlyEncoded()
    {
        $uri = (new Uri())->withFragment('/p^th?key^=`bar#b@z');
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $this->assertSame($expected, $uri->getFragment());
    }

    public function testFragmentIsNotDoubleEncoded()
    {
        $expected = '/p%5Eth?key%5E=%60bar%23b@z';
        $uri = (new Uri())->withFragment($expected);
        $this->assertSame($expected, $uri->getFragment());
    }
}