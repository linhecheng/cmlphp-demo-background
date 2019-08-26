<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-21 下午2:23
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 Http扩展类
 * *********************************************************** */

namespace Cml\Vendor;

/**
 * Http扩展类,用于采集、文件下载等
 *
 * @package Cml\Vendor
 */
class Http
{

    /**
     * 采集远程文件
     *
     * @param string $remote 远程文件名
     * @param string $local 本地保存文件名
     *
     * @return void
     */
    public static function curlDownload($remote, $local)
    {
        $cp = curl_init($remote);
        $fp = fopen($local, "w");
        curl_setopt($cp, CURLOPT_FILE, $fp);
        curl_setopt($cp, CURLOPT_HEADER, 0);
        curl_exec($cp);
        curl_close($cp);
        fclose($fp);
    }

    /**
     * 使用 fsockopen 通过 HTTP 协议直接访问(采集)远程文件
     * 如果主机或服务器没有开启 CURL 扩展可考虑使用
     * fsockopen 比 CURL 稍慢,但性能稳定
     *
     * @param string $url 远程URL
     * @param array $conf 其他配置信息
     *        int   limit 分段读取字符个数
     *        string post  post的内容,字符串或数组,key=value&形式
     *        string cookie 携带cookie访问,该参数是cookie内容
     *        string ip    如果该参数传入,$url将不被使用,ip访问优先
     *        int    timeout 采集超时时间
     *        bool   block 是否阻塞访问,默认为true
     * @param int $timeout 超时时间
     *
     * @return mixed
     */
    public static function fsockopenDownload($url, $conf = [], $timeout = 60)
    {
        $return = '';
        if (!is_array($conf)) {
            return $return;
        }

        $matches = parse_url($url);
        isset($matches['host']) || $matches['host'] = '';
        isset($matches['path']) || $matches['path'] = '';
        isset($matches['query']) || $matches['query'] = '';
        isset($matches['port']) || $matches['port'] = '';
        $host = $matches['host'];
        $path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
        $port = !empty($matches['port']) ? $matches['port'] : 80;

        $confArr = [
            'limit' => 0,
            'post' => '',
            'cookie' => '',
            'ip' => '',
            'timeout' => 15,
            'block' => true,
        ];

        foreach (array_merge($confArr, $conf) as $k => $v) ${$k} = $v;
        $post = '';
        if ($conf['post']) {
            if (is_array($conf['post'])) {
                $post = http_build_query($conf['post']);
            }
            $out = "POST $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= 'Content-Length: ' . strlen($conf['post']) . "\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cache-Control: no-cache\r\n";
            $out .= "Cookie: " . $conf['cookie'] . "\r\n\r\n";
            $out .= $post;
        } else {
            $out = "GET $path HTTP/1.0\r\n";
            $out .= "Accept: */*\r\n";
            //$out .= "Referer: $boardurl\r\n";
            $out .= "Accept-Language: zh-cn\r\n";
            $out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
            $out .= "Host: $host\r\n";
            $out .= "Connection: Close\r\n";
            $out .= "Cookie: " . $conf['cookie'] . "\r\n\r\n";
        }
        $fp = fsockopen(($conf['ip'] ? $conf['ip'] : $host), $port, $errno, $errstr, $timeout);
        if (!$fp) {
            return '';
        } else {
            stream_set_blocking($fp, $conf['block']);
            stream_set_timeout($fp, $timeout);
            fwrite($fp, $out);
            $status = stream_get_meta_data($fp);
            if (!$status['timed_out']) {
                while (!feof($fp)) {
                    if (($header = @fgets($fp)) && ($header == "\r\n" || $header == "\n")) {
                        break;
                    }
                }

                $stop = false;
                while (!feof($fp) && !$stop) {
                    $data = fread($fp, ($conf['limit'] == 0 || $conf['limit'] > 8192 ? 8192 : $conf['limit']));
                    $return .= $data;
                    if ($conf['limit']) {
                        $conf['limit'] -= strlen($data);
                        $stop = $conf['limit'] <= 0;
                    }
                }
            }
            fclose($fp);
            return $return;
        }
    }

    /**
     * 下载文件
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
     *
     * @param string $filename 下载文件名/要下载的文件的绝对地址
     * @param string $showName 下载显示的文件名
     * @param int $speedLimit 是否限速
     * @param string $dir 当$filename不带路径时。使用本参数的目录做为基础目录
     *
     * @return bool
     */
    public static function download($filename, $showName = '', $speedLimit = 0, $dir = CML_PROJECT_PATH . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR)
    {
        if (!is_file($filename)) {
            $filename = $dir . $filename;
        }
        if (!is_file($filename)) {
            header("HTTP/1.1 404 Not Found");
            return false;
        }

        if (empty($showName)) {
            $showName = $filename;
        }
        $showName = basename($showName);

        $contentType = self::mimeContentType($filename);

        $fileStat = stat($filename);
        $lastModified = $fileStat['mtime'];
        $md5 = md5($fileStat['mtime'] . '=' . $fileStat['ino'] . '=' . $fileStat['size']);
        $etag = '"' . $md5 . '-' . crc32($md5) . '"';
        header('Last-Modified: ' . gmdate("D, d M Y H:i:s", $lastModified) . ' GMT');
        header("ETag: $etag");
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
            header("HTTP/1.1 304 Not Modified");
            return true;
        }
        if (isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) < $lastModified) {
            header("HTTP/1.1 304 Not Modified");
            return true;
        }
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
            header("HTTP/1.1 304 Not Modified");
            return true;
        }

        $fileSize = $fileStat['size'];
        $contentLength = $fileSize;
        $isPartial = false;
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/^bytes=(d*)-(d*)$/', $_SERVER['HTTP_RANGE'], $matches)) {
                $startPos = $matches[1];
                $endPos = $matches[2];
                if ($startPos == '' && $endPos == '') {
                    return false;
                }
                if ($startPos == '') {
                    $startPos = $fileSize - $endPos;
                    $endPos = $fileSize - 1;
                } else if ($endPos == '') {
                    $endPos = $fileSize - 1;
                }
                $startPos = $startPos < 0 ? 0 : $startPos;
                $endPos = $endPos > $fileSize - 1 ? $fileSize - 1 : $endPos;
                $length = $endPos - $startPos + 1;
                if ($length <= 0) {
                    return false;
                }
                $contentLength = $length;
                $isPartial = true;
            }
        }

        if ($isPartial) {
            header('HTTP/1.1 206 Partial Content');
            header("Content-Range: bytes {$startPos} - {$endPos} / {$fileSize}");
        } else {
            header("HTTP/1.1 200 OK");
            $startPos = 0;
            //$endPos = $contentLength - 1;
        }

        header('Pragma: cache');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-type: ' . $contentType);
        header('Content-Length: ' . $contentLength);
        header('Content-Disposition: attachment; filename="' . rawurlencode($showName) . '"');
        header("Content-Transfer-Encoding: binary");

        $bufferSize = 2048;
        if ($speedLimit != 0) {
            $packetTime = floor($bufferSize * 1000000 / $speedLimit);
        }
        $bytesSent = 0;
        $fp = fopen($filename, "rb");
        fseek($fp, $startPos);
        while ($bytesSent < $contentLength && !feof($fp) && connection_status() == 0) {
            if ($speedLimit != 0) {
                $outputTimeStart = microtime(true);
            }
            $readBufferSize = $contentLength - $bytesSent < $bufferSize ? $contentLength - $bytesSent : $bufferSize;

            $buffer = fread($fp, $readBufferSize);
            echo $buffer;
            $bytesSent += $readBufferSize;
            if ($speedLimit != 0) {
                $outputTimeEnd = microtime(true);
                $useTime = ((float)$outputTimeEnd - (float)$outputTimeStart) * 1000000;
                $sleepTime = round($packetTime - $useTime);
                if ($sleepTime > 0) {
                    usleep($sleepTime);
                }
            }
        }
        return true;
    }

    /**
     * 显示HTTP Header 信息
     *
     * @param string $header
     * @param bool $echo
     *
     * @return string
     */
    public static function getHeaderInfo($header = '', $echo = true)
    {
        ob_start();
        $headers = getallheaders();
        if (!empty($header)) {
            $info = $headers[$header];
            echo($header . ':' . $info . "\n");;
        } else {
            foreach ($headers as $key => $val) {
                echo("$key:$val\n");
            }
        }
        $output = ob_get_clean();
        if ($echo) {
            echo(nl2br($output));
        } else {
            return $output;
        }
        return '';
    }

    /**
     * HTTP Protocol defined status codes
     *
     * @param int $code
     */
    public static function sendHttpStatus($code)
    {
        static $_status = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',

            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',

            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',

            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',

            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded'
        ];
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        }
    }

    /**
     * 获取文件的mime_content类型
     *
     * @param  string $filename
     *
     * @return string
     */
    public static function mimeContentType($filename)
    {
        static $contentType = [
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asc' => 'application/pgp', //changed by skwashd - was text/plain
            'asf' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'c' => 'text/plain', // or 'text/x-csrc', //added by skwashd
            'cc' => 'text/plain', // or 'text/x-c++src', //added by skwashd
            'cs' => 'text/plain', //added by skwashd - for C# src
            'cpp' => 'text/x-c++src', //added by skwashd
            'cxx' => 'text/x-c++src', //added by skwashd
            'cdf' => 'application/x-netcdf',
            'class' => 'application/octet-stream',//secure but application/java-class is correct
            'com' => 'application/octet-stream',//added by skwashd
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'csv' => 'text/comma-separated-values',//added by skwashd
            'dcr' => 'application/x-director',
            'diff' => 'text/diff',
            'dir' => 'application/x-director',
            'dll' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dot' => 'application/msword',//added by skwashd
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'h' => 'text/plain', // or 'text/x-chdr',//added by skwashd
            'h++' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hh' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hpp' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hxx' => 'text/plain', // or 'text/x-c++hdr', //added by skwashd
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference/x-cooltalk',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'ifb' => 'text/calendar',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jar' => 'application/x-jar', //added by skwashd - alternative mime type
            'java' => 'text/x-java-source', //added by skwashd
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'kar' => 'audio/midi',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'log' => 'text/plain',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'patch' => 'text/diff',
            'pbm' => 'image/x-portable-bitmap',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'pgp' => 'application/pgp',//added by skwashd
            'php' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php3',
            'pl' => 'application/x-perl',
            'pm' => 'application/x-perl',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'po' => 'text/plain',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-realaudio',
            'rar' => 'application/octet-stream',
            'ram' => 'audio/x-pn-realaudio',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'rm' => 'audio/x-pn-realaudio',
            'roff' => 'application/x-troff',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'shtml' => 'text/html',
            'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'stc' => 'application/vnd.sun.xml.calc.template',
            'std' => 'application/vnd.sun.xml.draw.template',
            'sti' => 'application/vnd.sun.xml.impress.template',
            'stw' => 'application/vnd.sun.xml.writer.template',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'swf' => 'application/x-shockwave-flash',
            'sxc' => 'application/vnd.sun.xml.calc',
            'sxd' => 'application/vnd.sun.xml.draw',
            'sxg' => 'application/vnd.sun.xml.writer.global',
            'sxi' => 'application/vnd.sun.xml.impress',
            'sxm' => 'application/vnd.sun.xml.math',
            'sxw' => 'application/vnd.sun.xml.writer',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tgz' => 'application/x-gtar',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vbs' => 'text/plain', //added by skwashd - for obvious reasons
            'vcd' => 'application/x-cdlink',
            'vcf' => 'text/x-vcard',
            'vcs' => 'text/calendar',
            'vfb' => 'text/calendar',
            'vrml' => 'model/vrml',
            'vsd' => 'application/vnd.visio',
            'wav' => 'audio/x-wav',
            'wax' => 'audio/x-ms-wax',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wm' => 'video/x-ms-wm',
            'wma' => 'audio/x-ms-wma',
            'wmd' => 'application/x-ms-wmd',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wmv' => 'video/x-ms-wmv',
            'wmx' => 'video/x-ms-wmx',
            'wmz' => 'application/x-ms-wmz',
            'wrl' => 'model/vrml',
            'wvx' => 'video/x-ms-wvx',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'text/xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'z' => 'application/x-compress',
            'zip' => 'application/zip',
        ];
        $type = strtolower(substr(strrchr($filename, '.'), 1));
        if (isset($contentType[$type])) {
            $mime = $contentType[$type];
        } else {
            $mime = 'application/octet-stream';
        }
        return $mime;
    }
}
