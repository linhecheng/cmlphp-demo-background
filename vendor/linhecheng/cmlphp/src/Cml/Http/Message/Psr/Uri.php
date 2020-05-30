<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午2:51
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 框架适配 Psr7 UriInterface实现
 * *********************************************************** */

namespace Cml\Http\Message\Psr;

use InvalidArgumentException;

/**
 * Psr\Http\Message\UriInterface实现
 * 通过 HTTP 请求上传的一个文件内容。
 *
 * @package Cml\Http\Message\Psr
 */
class Uri
{
    private const SCHEMES = ['http' => 80, 'https' => 443];

    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    /**
     * Uri scheme.
     *
     * @var string
     */
    private $scheme = '';

    /**
     * Uri user info.
     *
     * @var string
     */
    private $userInfo = '';

    /**
     * Uri host.
     *
     * @var string
     */
    private $host = '';

    /**
     *  Uri port.
     *
     * @var int|null
     */
    private $port;

    /**
     *  Uri path.
     *
     * @var string
     */
    private $path = '';

    /**
     * Uri query string.
     *
     * @var string
     */
    private $query = '';

    /**
     * Uri fragment.
     *
     * @var string
     */
    private $fragment = '';

    public function __construct($uri = '')
    {
        if ('' !== $uri) {
            if (false === $parts = parse_url($uri)) {
                throw new InvalidArgumentException("Unable to parse URI: $uri");
            }

            // Apply parse_url parts to a URI.
            $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
            $this->userInfo = $parts['user'] ?? '';
            $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    public function __toString()
    {
        return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
    }

    /**
     * 从 URI 中取出 scheme。如果不存在 Scheme，返回空字符串。
     * 根据 RFC 3986 规范 3.1 章节，返回的数据是小写字母。
     * 最后部分的「:」字串不属于 Scheme，不作为返回数据的一部分。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     *
     * @return string URI Ccheme 的值。
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * 返回 URI 认证信息。如果没有 URI 认证信息的话返回一个空字符串。
     * URI 的认证信息语法是：
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * 如果端口部分没有设置，或者端口不是标准端口，不包含在返回值内。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @return string URI 认证信息，格式为：「[user-info@]host[:port]」。
     */
    public function getAuthority()
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;
        if ('' !== $this->userInfo) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * 从 URI 中获取用户信息。
     * 如果不存在用户信息，此方法返回一个空字符串。
     *
     * 如果 URI 中存在用户，则返回该值；此外，如果密码也存在，它将附加到用户值，用冒号（「:」）分隔。
     *
     * 用户信息后面跟着的 "@" 字符，不是用户信息里面的一部分，不在返回值里出现。
     *
     * @return string URI 的用户信息，格式："username[:password]"
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * 从 URI 中获取 HOST 信息。
     * 如果 URI 中没有此值，返回空字符串。
     *
     * 根据 RFC 3986 规范 3.2.2 章节，返回的数据是小写字母。@see http://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @return string URI 中的 HOST 信息。
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * 从 URI 中获取端口信息。
     * 如果不存在端口和 Scheme 信息，返回 `null` 值。
     *
     * @return null|int URI 中的端口信息。
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 从 URI 中获取路径信息。
     * 路径可以是空的，或者是绝对的（以斜线「/」开头），或者相对路径（不以斜线开头）。
     *
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @return string URI 路径信息。
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * 获取 URI 中的查询字符串。
     *
     * 如果不存在查询字符串，则此方法返回空字符串。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     *
     * @return string URI 中的查询字符串
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * 获取 URI 中的片段（Fragment）信息。
     * 如果没有片段信息，此方法返回空字符串。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * @return string URI 中的片段信息。
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * 返回具有指定 Scheme 的实例。
     * 此方保留当前实例的状态，并返回包含指定 Scheme 的实例。
     * 空的 Scheme 相当于删除 Scheme。
     *
     * @param string $scheme 给新实例使用的 Scheme。
     *
     * @return self 具有指定 Scheme 的新实例。
     *
     * @throws InvalidArgumentException 使用无效的 Scheme 时抛出。| 使用不支持的 Scheme 时抛出。
     */
    public function withScheme($scheme)
    {
        if (!is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string');
        }

        if ($this->scheme === $scheme = strtolower($scheme)) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /**
     * 返回具有指定用户信息的实例。
     *
     *
     * 密码是可选的，但用户信息 **必须** 包括用户；用户信息的空字符串相当于删除用户信息。
     *
     * @param string $user 用于认证的用户名。
     * @param null|string $password 密码。
     *
     * @return self 具有指定用户信息的新实例。
     */
    public function withUserInfo($user, $password = null)
    {
        $info = $user;
        if (null !== $password && '' !== $password) {
            $info .= ':' . $password;
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * 返回具有指定 HOST 信息的实例。
     *
     *
     * 空的 HOST 信息等同于删除 HOST 信息。
     *
     * @param string $host 用于新实例的 HOST 信息。
     *
     * @return self 具有指定 HOST 信息的实例。
     *
     * @throws InvalidArgumentException 使用无效的 HOST 信息时抛出。
     */
    public function withHost($host)
    {
        if (!is_string($host)) {
            throw new InvalidArgumentException('Host must be a string');
        }

        if ($this->host === $host = strtolower($host)) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * 返回具有指定端口的实例。
     *
     * 为端口提供的空值等同于删除端口信息。
     *
     * @param null|int $port 用于新实例的端口；`null` 值将删除端口信息。
     *
     * @return self 具有指定端口的实例。
     *
     * @throws InvalidArgumentException 使用无效端口时抛出异常。
     */
    public function withPort($port)
    {
        if ($this->port === $port = $this->filterPort($port)) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * 返回具有指定路径的实例。
     *
     * @param string $path 用于新实例的路径。
     *
     * @return self 具有指定路径的实例。
     *
     * @throws InvalidArgumentException 使用无效的路径时抛出。
     */
    public function withPath($path)
    {
        if ($this->path === $path = $this->filterPath($path)) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * 返回具有指定查询字符串的实例。
     * 空查询字符串值等同于删除查询字符串。
     *
     * @param string $query 用于新实例的查询字符串。
     *
     * @return self 具有指定查询字符串的实例。
     *
     * @throws InvalidArgumentException 使用无效的查询字符串时抛出。
     */
    public function withQuery($query)
    {
        if ($this->query === $query = $this->filterQueryAndFragment($query)) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * 返回具有指定 URI 片段（Fragment）的实例。
     *
     * 用户可以提供编码和解码的片段，要确保实现了 `getFragment()` 中描述的正确编码。
     *
     * 空片段值等同于删除片段。
     *
     * @param string $fragment 用于新实例的片段。
     *
     * @return self 具有指定 URI 片段的实例。
     */
    public function withFragment($fragment)
    {
        if ($this->fragment === $fragment = $this->filterQueryAndFragment($fragment)) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * 创建URI字符串
     *
     * @param string $scheme
     * @param string $authority
     * @param string $path
     * @param string $query
     * @param string $fragment
     *
     * @return string
     */
    private static function createUriString($scheme, $authority, $path, $query, $fragment)
    {
        $uri = '';
        if ('' !== $scheme) {
            $uri .= $scheme . ':';
        }

        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }

        if ('' !== $path) {
            if ('/' !== $path[0]) {
                if ('' !== $authority) {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && '/' === $path[1]) {
                if ('' === $authority) {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = '/' . ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ('' !== $query) {
            $uri .= '?' . $query;
        }

        if ('' !== $fragment) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * 判断端口是否标准
     *
     * @param string $scheme
     * @param int $port
     *
     * @return bool
     */
    private static function isNonStandardPort($scheme, $port)
    {
        return !isset(self::SCHEMES[$scheme]) || $port !== self::SCHEMES[$scheme];
    }

    /**
     * 过滤端口
     *
     * @param $port
     *
     * @return int|null
     */
    private function filterPort($port)
    {
        if (null === $port) {
            return null;
        }

        $port = (int)$port;
        if (0 > $port || 0xffff < $port) {
            throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
        }

        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    /**
     * 过滤路径
     *
     * @param $path
     *
     * @return int|null
     */
    private function filterPath($path)
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }

        return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $path);
    }

    /**
     * 过滤query和Fragment参数
     *
     * @param $str
     *
     * @return string|string[]|null
     */
    private function filterQueryAndFragment($str)
    {
        if (!is_string($str)) {
            throw new InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $str);
    }

    /**
     * 编码
     *
     * @param array $match
     *
     * @return string
     */
    private static function rawurlencodeMatchZero(array $match)
    {
        return rawurlencode($match[0]);
    }
}
