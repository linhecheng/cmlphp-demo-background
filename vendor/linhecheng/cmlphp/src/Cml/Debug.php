<?php
/* * *********************************************************
 * [cmlphp] (C)2012 - 3000 http://cmlphp.com
 * @Author  linhecheng<linhechengbush@live.com>
 * @Date: 14-2-8 下午3:07
 * @version  @see \Cml\Cml::VERSION
 * cmlphp框架 系统DEBUG调试类
 * *********************************************************** */

namespace Cml;

use Cml\Http\Request;
use \Cml\Interfaces\Debug as DebugInterfaces;

/**
 * Debug调试处理类,debug=true时负责调试相关信息的收集及ui的展示
 *
 * @package Cml
 */
class Debug implements DebugInterfaces
{
    private static $includeFile = [];//消息的类型为包含文件
    private static $includeLib = [];//消息的类型为包含文件
    private static $tipInfo = [];//消息的类型为普通消息
    private static $sql = [];//消息的类型为sql
    private static $stopTime;//程序运行结束时间
    private static $startMemory;//程序开始运行所用内存
    private static $stopMemory;//程序结束运行时所用内存
    private static $tipInfoType = [
        E_WARNING => '运行时警告',
        E_NOTICE => '运行时提醒',
        E_STRICT => '编码标准化警告',
        E_USER_ERROR => '自定义错误',
        E_USER_WARNING => '自定义警告',
        E_USER_NOTICE => '自定义提醒',
        E_DEPRECATED => '过时函数提醒',
        E_RECOVERABLE_ERROR => '可捕获的致命错误',
        'Unknow' => '未知错误'
    ];

    /**
     * 控制debug模式是否要显示调试工具栏，比如输出vue子组件就不应该输出
     *
     * @var bool
     */
    private static $debugModelIsShowDebugInfo = true;


    /**
     * info类型的提示信息
     *
     * @var int
     */
    const TIP_INFO_TYPE_INFO = 0;

    /**
     * 包含文件的提示信息
     *
     * @var int
     */
    const TIP_INFO_TYPE_INCLUDE_LIB = 1;

    /**
     * SQL语句调试信息
     *
     * @var int
     */
    const TIP_INFO_TYPE_SQL = 2;

    /**
     * 包含文件的提示信息
     *
     * @var int
     */
    const TIP_INFO_TYPE_INCLUDE_FILE = 3;

    /**
     * 正常的sql执行语句
     *
     * @var int
     */
    const SQL_TYPE_NORMAL = 1;

    /**
     * 该SQL执行结果直接从缓存返回
     *
     * @var int
     */
    const SQL_TYPE_FROM_CACHE = 2;

    /**
     * 执行过慢的sql
     *
     * @var int
     */
    const SQL_TYPE_SLOW = 3;

    /**
     * 返回执行的sql语句
     *
     * @return array
     */
    public static function getSqls()
    {
        return self::$sql;
    }

    /**
     * 返回提示信息
     *
     * @return array
     */
    public static function getTipInfo()
    {
        return self::$tipInfo;
    }

    /**
     * 返回包含的文件
     *
     * @return array
     */
    public static function getIncludeFiles()
    {
        return self::$includeFile;
    }

    /**
     * 返回包含的类库
     *
     * @return array
     */
    public static function getIncludeLib()
    {
        return self::$includeLib;
    }


    /**
     * 在脚本开始处调用获取脚本开始时间的微秒值\及内存的使用量
     *
     */
    public static function start()
    {
        // 记录内存初始使用
        function_exists('memory_get_usage') && self::$startMemory = memory_get_usage();
    }

    /**
     * 控制debug模式是否要显示调试工具栏，比如输出vue子组件就不应该输出(设置为false)
     *
     * @param bool $isShow
     */
    public static function setDebugModelIsShowDebugInfo($isShow = true)
    {
        self::$debugModelIsShowDebugInfo = $isShow;
    }


    /**
     * 程序执行完毕,打印CmlPHP运行信息
     *
     */
    public static function stop()
    {
        self::$stopTime = microtime(true);
        // 记录内存结束使用
        function_exists('memory_get_usage') && self::$stopMemory = memory_get_usage();
        
        self::$debugModelIsShowDebugInfo && Cml::getContainer()->make('cml_debug')->stopAndShowDebugInfo();
    }

    /**
     * 返回程序运行所消耗时间
     *
     * @return float
     */
    public static function getUseTime()
    {
        return round((self::$stopTime - Cml::$nowMicroTime), 4);  //计算后以4舍5入保留4位返回
    }

    /**
     * 返回程序运行所消耗的内存
     *
     * @return string
     */
    public static function getUseMemory()
    {
        if (function_exists('memory_get_usage')) {
            return number_format((self::$stopMemory - self::$startMemory) / 1024, 2) . 'kb';
        } else {
            return '当前服务器环境不支持内存消耗统计';
        }
    }

    /**
     * 错误handler
     *
     * @param int $errorType 错误类型 分运行时警告、运行时提醒、自定义错误、自定义提醒、未知等
     * @param string $errorTip 错误提示
     * @param string $errorFile 发生错误的文件
     * @param int $errorLine 错误所在行数
     *
     * @return void
     */
    public static function catcher($errorType, $errorTip, $errorFile, $errorLine)
    {
        if (!isset(self::$tipInfoType[$errorType])) {
            $errorType = 'Unknow';
        }
        if ($errorType == E_NOTICE || $errorType == E_USER_NOTICE) {
            $color = '#000088';
        } else {
            $color = 'red';
        }
        $mess = "<span style='color:{$color}'>";
        $mess .= '<b>' . self::$tipInfoType[$errorType] . "</b>[在文件 {$errorFile} 中,第 {$errorLine} 行]:";
        $mess .= $errorTip;
        $mess .= '</span>';
        self::addTipInfo($mess);
    }

    /**
     * 添加调试信息
     *
     * @param string $msg 调试消息字符串
     * @param int $type 消息的类型
     * @param string $color 是否要添加字体颜色
     *
     * @return void
     */
    public static function addTipInfo($msg, $type = self::TIP_INFO_TYPE_INFO, $color = '')
    {
        if (Cml::$debug) {
            $color && $msg = "<span style='color:{$color}'>" . $msg . '</span>';
            switch ($type) {
                case self::TIP_INFO_TYPE_INFO:
                    self::$tipInfo[] = $msg;
                    break;
                case self::TIP_INFO_TYPE_INCLUDE_LIB:
                    self::$includeLib[] = $msg;
                    break;
                case self::TIP_INFO_TYPE_SQL:
                    self::$sql[] = $msg;
                    break;
                case self::TIP_INFO_TYPE_INCLUDE_FILE:
                    self::$includeFile[] = str_replace('\\', '/', str_replace([Cml::getApplicationDir('secure_src'), CML_PATH], ['{secure_src}', '{cmlphp_src}'], $msg));
                    break;
            }
        }
    }

    /**
     * 添加一条sql查询的调试信息
     *
     * @param $sql
     * @param int $type sql类型 参考常量声明SQL_TYPE_NORMAL、SQL_TYPE_FROM_CACHE、SQL_TYPE_SLOW
     * @param int $other type = SQL_TYPE_SLOW时带上执行时间
     */
    public static function addSqlInfo($sql, $type = self::SQL_TYPE_NORMAL, $other = 0)
    {
        switch ($type) {
            case self::SQL_TYPE_FROM_CACHE:
                $sql .= "<span style='color:red;'>[from cache]</span>";
                break;
            case self::SQL_TYPE_SLOW:
                $sql .= "<span style='color:red;'>[slow sql, {$other}]</span>";
                break;
        }
        self::addTipInfo($sql, self::TIP_INFO_TYPE_SQL);
    }

    /**
     * 高亮显示代码片段
     *
     * @param string $file 文件路径
     * @param int $focus 出错的行
     * @param int $range 基于出错行上下显示多少行
     * @param array $style 样式
     *
     * @return string
     */
    public static function codeSnippet($file, $focus, $range = 7, $style = ['lineHeight' => 20, 'fontSize' => 14])
    {
        ini_set("highlight.comment", "#008000");
        ini_set("highlight.default", "#000000");
        ini_set("highlight.html", "#808080");
        ini_set("highlight.keyword", "#0000BB;");
        ini_set("highlight.string", "#DD0000");

        $html = highlight_file($file, true);
        if (!$html) {
            return false;
        }
        // 分割html保存到数组
        $html = explode('<br />', $html);
        $lineNums = count($html);
        // 代码的html
        $codeHtml = '';

        // 获取相应范围起止索引
        $start = ($focus - $range) < 1 ? 0 : ($focus - $range - 1);
        $end = (($focus + $range) > $lineNums ? $lineNums - 1 : ($focus + $range - 1));

        // 修正开始标签
        // 有可能取到的片段缺少开始的span标签，而它包含代码着色的CSS属性
        // 如果缺少，片段开始的代码则没有颜色了，所以需要把它找出来
        if (substr($html[$start], 0, 5) !== '<span') {
            while (($start - 1) >= 0) {
                $match = [];
                preg_match('/<span style="color: #([\w]+)"(.(?!<\/span>))+$/', $html[--$start], $match);
                if (!empty($match)) {
                    $html[$start] = "<span style=\"color: #{$match[1]}\">" . $html[$start];
                    break;
                }
            }
        }

        for ($line = $start; $line <= $end; $line++) {
            // 在行号前填充0
            $indexPad = str_pad($line + 1, strlen($end), 0, STR_PAD_LEFT);
            ($line + 1) == $focus && $codeHtml .= "<p style='height: " . $style['lineHeight'] . "px; width: 100%; border-radius:3px; background-color: #ffa39e;'>";
            $codeHtml .= "<span class='code-line' style='line-height: " . $style['lineHeight'] . "px; '>{$indexPad}.</span>{$html[$line]}";
            $codeHtml .= (($line + 1) == $focus ? '</p>' : ($line != $end ? '<br />' : ''));
        }

        // 修正结束标签
        if (substr($codeHtml, -7) !== '</span>') {
            $codeHtml .= '</span>';
        }

        return <<<EOT
        <div style="position: relative; font-size: {$style['fontSize']}px; background-color: #f2f8f0;padding:8px 5px;border-radius: 8px;">
            <div style="_width: 95%; line-height: {$style['lineHeight']}px; position: relative; z-index: 2; overflow: hidden; white-space:nowrap; text-overflow:ellipsis;">{$codeHtml}</div>
        </div>
EOT;
    }

    /**
     * 输出调试消息
     *
     * @return void
     */
    public function stopAndShowDebugInfo()
    {
        if (Request::isAjax()) {
            $dump = [
                'sql' => self::$sql,
                'tipInfo' => self::$tipInfo
            ];
            if (Config::get('dump_use_php_console')) {
                dumpUsePHPConsole($dump, strip_tags($_SERVER['REQUEST_URI']));
            } else {
                Cml::requireFile(CML_CORE_PATH . DIRECTORY_SEPARATOR . 'ConsoleLog.php', ['deBugLogData' => $dump]);
            }
        } else {
            View::getEngine('html')
                ->assign('includeLib', Debug::getIncludeLib())
                ->assign('includeFile', Debug::getIncludeFiles())
                ->assign('tipInfo', Debug::getTipInfo())
                ->assign('sqls', Debug::getSqls())
                ->assign('usetime', Debug::getUseTime())
                ->assign('usememory', Debug::getUseMemory());
            Cml::showSystemTemplate(Config::get('debug_page'));
        }
    }
}

/**
 * 修改自dbug官方网站 http://dbug.ospinto.com/
 *
 * 使用方法：
 * include_once("dBug.php");
 * new dBug($myVariable1);
 * new dBug($myVariable2); //建议每次都创建一个新实例
 * new dBug($arr);
 *
 * $test = new someClass('123');
 * new dBug($test);
 *
 * $result = mysql_query('select * from tblname');
 * new dBug($result);
 *
 * $xmlData = "./data.xml";
 * new dBug($xmlData, "xml");
 **/

/**
 * debug依赖的第三方库
 *
 * @package Cml
 */
class dBug
{
    private $xmlCData;
    private $xmlSData;
    private $xmlDData;
    private $xmlCount = 0;
    private $xmlAttrib;
    private $xmlName;
    private $arrType = ["array", "object", "resource", "boolean", "NULL"];
    private $bInitialized = false;
    private $bCollapsed = false;
    private $arrHistory = [];

    /**
     * 构造方法
     *
     * @param mixed $var 要打印的变量
     * @param string $forceType
     * @param bool $bCollapsed
     */
    public function __construct($var, $forceType = "", $bCollapsed = false)
    {
        //include js and css scripts
        $this->initJSandCSS();
        $arrAccept = ["array", "object", "xml"]; //array of variable types that can be "forced"
        $this->bCollapsed = $bCollapsed;
        if (in_array($forceType, $arrAccept)) {
            $this->{"varIs" . ucfirst($forceType)}($var);
        } else {
            $this->checkType($var);
        }
    }

    //get variable name
    private function getVariableName()
    {
        $arrBacktrace = debug_backtrace();

        //possible 'included' functions
        $arrInclude = ["include", "include_once", "require", "require_once"];

        //check for any included/required files. if found, get array of the last included file (they contain the right line numbers)
        for ($i = count($arrBacktrace) - 1; $i >= 0; $i--) {
            $arrCurrent = $arrBacktrace[$i];
            if (
                array_key_exists("function", $arrCurrent)
                && (
                    in_array($arrCurrent["function"], $arrInclude) || (0 != strcasecmp($arrCurrent["function"], "dbug"))
                )
            ) {
                continue;
            }
            $arrFile = $arrCurrent;
            break;
        }

        if (isset($arrFile)) {
            $arrLines = file($arrFile["file"]);
            $code = $arrLines[($arrFile["line"] - 1)];

            //find call to dBug class
            preg_match('/\bnew dBug\s*\(\s*(.+)\s*\);/i', $code, $arrMatches);

            return $arrMatches[1];
        }
        return "";
    }

    //create the main table header
    private function makeTableHeader($type, $header, $colspan = 2)
    {
        if (!$this->bInitialized) {
            $header = $this->getVariableName() . " (" . $header . ")";
            $this->bInitialized = true;
        }
        $str_i = ($this->bCollapsed) ? "style=\"font-style:italic\" " : "";

        echo "<table cellspacing=2 cellpadding=3 class=\"dBug_" . $type . "\">
                <tr>
                    <td " . $str_i . "class=\"dBug_" . $type . "Header\" colspan=" . $colspan . " onClick='dBug_toggleTable(this)'>" . $header . "</td>
                </tr>";
    }

    //create the table row header
    private function makeTDHeader($type, $header)
    {
        $str_d = ($this->bCollapsed) ? " style=\"display:none\"" : "";
        echo "<tr" . $str_d . ">
                <td valign=\"top\" onClick='dBug_toggleRow(this)' class=\"dBug_" . $type . "Key\">" . $header . "</td>
                <td>";
    }

    //close table row
    private function closeTDRow()
    {
        return "</td></tr>\n";
    }

    //error
    private function error($type)
    {
        $error = "Error: Variable cannot be a";
        // this just checks if the type starts with a vowel or "x" and displays either "a" or "an"
        if (
        in_array(substr($type, 0, 1), ["a", "e", "i", "o", "u", "x"])
        ) {
            $error .= "n";
        }
        return ($error . " " . $type . " type");
    }

    //check variable type
    private function checkType($var)
    {
        switch (gettype($var)) {
            case "resource":
                $this->varIsResource($var);
                break;
            case "object":
                $this->varIsObject($var);
                break;
            case "array":
                $this->varIsArray($var);
                break;
            case "NULL":
                $this->varIsNULL();
                break;
            case "boolean":
                $this->varIsBoolean($var);
                break;
            default:
                $var = ($var == "") ? "[empty string]" : $var;
                echo "<table cellspacing=0><tr>\n<td>" . $var . "</td>\n</tr>\n</table>\n";
                break;
        }
    }

    //if variable is a NULL type
    private function varIsNULL()
    {
        echo "NULL";
    }

    //if variable is a boolean type
    private function varIsBoolean($var)
    {
        $var = ($var == 1) ? "true" : "false";
        echo $var;
    }

    //if variable is an array type
    private function varIsArray($var)
    {
        $var_ser = serialize($var);
        array_push($this->arrHistory, $var_ser);

        $this->makeTableHeader("array", "array");
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $this->makeTDHeader("array", $key);

                //check for recursion
                if (is_array($value)) {
                    $var_ser = serialize($value);
                    if (in_array($var_ser, $this->arrHistory, true))
                        $value = "*RECURSION*";
                }

                if (in_array(gettype($value), $this->arrType)) {
                    $this->checkType($value);
                } else {
                    $value = (trim($value) == "") ? "[empty string]" : $value;
                    echo $value;
                }
                echo $this->closeTDRow();
            }
        } else {
            echo "<tr><td>" . $this->error("array") . $this->closeTDRow();
        }
        array_pop($this->arrHistory);
        echo "</table>";
    }

    //if variable is an object type
    private function varIsObject($var)
    {
        $var_ser = serialize($var);
        array_push($this->arrHistory, $var_ser);
        $this->makeTableHeader("object", "object");

        if (is_object($var)) {
            $arrObjVars = get_object_vars($var);
            foreach ($arrObjVars as $key => $value) {

                $value = (!is_object($value) && !is_array($value) && trim($value) == "") ? "[empty string]" : $value;
                $this->makeTDHeader("object", $key);

                //check for recursion
                if (is_object($value) || is_array($value)) {
                    $var_ser = serialize($value);
                    if (in_array($var_ser, $this->arrHistory, TRUE)) {
                        $value = (is_object($value)) ? "*RECURSION* -> $" . get_class($value) : "*RECURSION*";

                    }
                }
                if (in_array(gettype($value), $this->arrType)) {
                    $this->checkType($value);
                } else {
                    echo $value;
                }
                echo $this->closeTDRow();
            }
            $arrObjMethods = get_class_methods(get_class($var));
            foreach ($arrObjMethods as $key => $value) {
                $this->makeTDHeader("object", $value);
                echo "[function]" . $this->closeTDRow();
            }
        } else {
            echo "<tr><td>" . $this->error("object") . $this->closeTDRow();
        }
        array_pop($this->arrHistory);
        echo "</table>";
    }

    //if variable is a resource type
    private function varIsResource($var)
    {
        $this->makeTableHeader("resourceC", "resource", 1);
        echo "<tr>\n<td>\n";
        switch (get_resource_type($var)) {
            case "fbsql result":
            case "mssql result":
            case "msql query":
            case "pgsql result":
            case "sybase-db result":
            case "sybase-ct result":
            case "mysql result":
                $db = current(explode(" ", get_resource_type($var)));
                $this->varIsDBResource($var, $db);
                break;
            case "gd":
                $this->varIsGDResource($var);
                break;
            case "xml":
                $this->varIsXmlResource($var);
                break;
            default:
                echo get_resource_type($var) . $this->closeTDRow();
                break;
        }
        echo $this->closeTDRow() . "</table>\n";
    }

    //if variable is a database resource type
    private function varIsDBResource($var, $db = "mysql")
    {
        if ($db == "pgsql") {
            $db = "pg";
        }
        if ($db == "sybase-db" || $db == "sybase-ct") {
            $db = "sybase";
        }
        $arrFields = ["name", "type", "flags"];
        $numrows = call_user_func($db . "_num_rows", $var);
        $numfields = call_user_func($db . "_num_fields", $var);
        $this->makeTableHeader("resource", $db . " result", $numfields + 1);
        echo "<tr><td class=\"dBug_resourceKey\">&nbsp;</td>";
        $field = [];
        for ($i = 0; $i < $numfields; $i++) {
            $field_header = $field_name = "";
            for ($j = 0; $j < count($arrFields); $j++) {
                $db_func = $db . "_field_" . $arrFields[$j];
                if (function_exists($db_func)) {
                    $fheader = call_user_func($db_func, $var, $i) . " ";
                    if ($j == 0) {
                        $field_name = $fheader;
                    } else {
                        $field_header .= $fheader;
                    }
                }
            }
            $field[$i] = call_user_func($db . "_fetch_field", $var, $i);
            echo "<td class=\"dBug_resourceKey\" title=\"" . $field_header . "\">" . $field_name . "</td>";
        }
        echo "</tr>";
        for ($i = 0; $i < $numrows; $i++) {
            $row = call_user_func($db . "_fetch_array", $var, constant(strtoupper($db) . "_ASSOC"));
            echo "<tr>\n";
            echo "<td class=\"dBug_resourceKey\">" . ($i + 1) . "</td>";
            for ($k = 0; $k < $numfields; $k++) {
                $fieldrow = $row[($field[$k]->name)];
                $fieldrow = ($fieldrow == "") ? "[empty string]" : $fieldrow;
                echo "<td>" . $fieldrow . "</td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>";
        if ($numrows > 0) {
            call_user_func($db . "_data_seek", $var, 0);
        }
    }

    //if variable is an image/gd resource type
    private function varIsGDResource($var)
    {
        $this->makeTableHeader("resource", "gd", 2);
        $this->makeTDHeader("resource", "Width");
        echo imagesx($var) . $this->closeTDRow();
        $this->makeTDHeader("resource", "Height");
        echo imagesy($var) . $this->closeTDRow();
        $this->makeTDHeader("resource", "Colors");
        echo imagecolorstotal($var) . $this->closeTDRow();
        echo "</table>";
    }

    //if variable is an xml type
    private function varIsXml($var)
    {
        $this->varIsXmlResource($var);
    }

    //if variable is an xml resource type
    private function varIsXmlResource($var)
    {
        $xml_parser = xml_parser_create();
        xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
        xml_set_element_handler($xml_parser, [&$this, "xmlStartElement"], [&$this, "xmlEndElement"]);
        xml_set_character_data_handler($xml_parser, [&$this, "xmlCharacterData"]);
        xml_set_default_handler($xml_parser, [&$this, "xmlDefaultHandler"]);

        $this->makeTableHeader("xml", "xml document", 2);
        $this->makeTDHeader("xml", "xmlRoot");

        //attempt to open xml file
        $bFile = (!($fp = @fopen($var, "r"))) ? false : true;

        //read xml file
        if ($bFile) {
            while ($data = str_replace("\n", "", fread($fp, 4096))) {
                $this->xmlParse($xml_parser, $data, feof($fp));
            }
        } //if xml is not a file, attempt to read it as a string
        else {
            if (!is_string($var)) {
                echo $this->error("xml") . $this->closeTDRow() . "</table>\n";
                return;
            }
            $data = $var;
            $this->xmlParse($xml_parser, $data, 1);
        }

        echo $this->closeTDRow() . "</table>\n";

    }

    //parse xml
    private function xmlParse($xml_parser, $data, $bFinal)
    {
        if (!xml_parse($xml_parser, $data, $bFinal)) {
            die(sprintf("XML error: %s at line %d\n",
                xml_error_string(xml_get_error_code($xml_parser)),
                xml_get_current_line_number($xml_parser)));
        }
    }

    //xml: inititiated when a start tag is encountered
    private function xmlStartElement($parser, $name, $attribs)
    {
        $this->xmlAttrib[$this->xmlCount] = $attribs;
        $this->xmlName[$this->xmlCount] = $name;
        $this->xmlSData[$this->xmlCount] = '$this->makeTableHeader("xml","xml element",2);';
        $this->xmlSData[$this->xmlCount] .= '$this->makeTDHeader("xml","xmlName");';
        $this->xmlSData[$this->xmlCount] .= 'echo "<strong>' . $this->xmlName[$this->xmlCount] . '</strong>".$this->closeTDRow();';
        $this->xmlSData[$this->xmlCount] .= '$this->makeTDHeader("xml","xmlAttributes");';
        if (count($attribs) > 0) {
            $this->xmlSData[$this->xmlCount] .= '$this->varIsArray($this->xmlAttrib[' . $this->xmlCount . ']);';
        } else {
            $this->xmlSData[$this->xmlCount] .= 'echo "&nbsp;";';
        }
        $this->xmlSData[$this->xmlCount] .= 'echo $this->closeTDRow();';
        $this->xmlCount++;
    }

    //xml: initiated when an end tag is encountered
    private function xmlEndElement($parser, $name)
    {
        for ($i = 0; $i < $this->xmlCount; $i++) {
            eval($this->xmlSData[$i]);
            $this->makeTDHeader("xml", "xmlText");
            echo (!empty($this->xmlCData[$i])) ? $this->xmlCData[$i] : "&nbsp;";
            echo $this->closeTDRow();
            $this->makeTDHeader("xml", "xmlComment");
            echo (!empty($this->xmlDData[$i])) ? $this->xmlDData[$i] : "&nbsp;";
            echo $this->closeTDRow();
            $this->makeTDHeader("xml", "xmlChildren");
            unset($this->xmlCData[$i], $this->xmlDData[$i]);
        }
        echo $this->closeTDRow();
        echo "</table>";
        $this->xmlCount = 0;
    }

    //xml: initiated when text between tags is encountered
    private function xmlCharacterData($parser, $data)
    {
        $count = $this->xmlCount - 1;
        if (!empty($this->xmlCData[$count])) {
            $this->xmlCData[$count] .= $data;
        } else {
            $this->xmlCData[$count] = $data;
        }
    }

    //xml: initiated when a comment or other miscellaneous texts is encountered
    private function xmlDefaultHandler($parser, $data)
    {
        //strip '<!--' and '-->' off comments
        $data = str_replace(["&lt;!--", "--&gt;"], "", htmlspecialchars($data));
        $count = $this->xmlCount - 1;
        if (!empty($this->xmlDData[$count])) {
            $this->xmlDData[$count] .= $data;
        } else {
            $this->xmlDData[$count] = $data;
        }
    }

    private function initJSandCSS()
    {
        echo <<<SCRIPTS
            <script language="JavaScript">
            /* code modified from ColdFusion's cfdump code */
                function dBug_toggleRow(source) {
                    var target = (document.all) ? source.parentElement.cells[1] : source.parentNode.lastChild;
                    dBug_toggleTarget(target,dBug_toggleSource(source));
                }

                function dBug_toggleSource(source) {
                    if (source.style.fontStyle=='italic') {
                        source.style.fontStyle='normal';
                        source.title='click to collapse';
                        return 'open';
                    } else {
                        source.style.fontStyle='italic';
                        source.title='click to expand';
                        return 'closed';
                    }
                }

                function dBug_toggleTarget(target,switchToState) {
                    target.style.display = (switchToState=='open') ? '' : 'none';
                }

                function dBug_toggleTable(source) {
                    var switchToState=dBug_toggleSource(source);
                    if (document.all) {
                        var table=source.parentElement.parentElement;
                        for (var i=1;i<table.rows.length;i++) {
                            target=table.rows[i];
                            dBug_toggleTarget(target,switchToState);
                        }
                    }
                    else {
                        var table=source.parentNode.parentNode;
                        for (var i=1;i<table.childNodes.length;i++) {
                            target=table.childNodes[i];
                            if (target.style) {
                                dBug_toggleTarget(target,switchToState);
                            }
                        }
                    }
                }
            </script>

            <style type="text/css">
                table.dBug_array,table.dBug_object,table.dBug_resource,table.dBug_resourceC,table.dBug_xml {
                    font-family:Verdana, Arial, Helvetica, sans-serif; color:#000000; font-size:12px;
                }

                .dBug_arrayHeader,
                .dBug_objectHeader,
                .dBug_resourceHeader,
                .dBug_resourceCHeader,
                .dBug_xmlHeader
                    { font-weight:bold; color:#FFFFFF; cursor:pointer; }

                .dBug_arrayKey,
                .dBug_objectKey,
                .dBug_xmlKey
                    { cursor:pointer; }

                /* array */
                table.dBug_array { background-color:#006600; }
                table.dBug_array td { background-color:#FFFFFF; }
                table.dBug_array td.dBug_arrayHeader { background-color:#009900; }
                table.dBug_array td.dBug_arrayKey { background-color:#CCFFCC; }

                /* object */
                table.dBug_object { background-color:#0000CC; }
                table.dBug_object td { background-color:#FFFFFF; }
                table.dBug_object td.dBug_objectHeader { background-color:#4444CC; }
                table.dBug_object td.dBug_objectKey { background-color:#CCDDFF; }

                /* resource */
                table.dBug_resourceC { background-color:#884488; }
                table.dBug_resourceC td { background-color:#FFFFFF; }
                table.dBug_resourceC td.dBug_resourceCHeader { background-color:#AA66AA; }
                table.dBug_resourceC td.dBug_resourceCKey { background-color:#FFDDFF; }

                /* resource */
                table.dBug_resource { background-color:#884488; }
                table.dBug_resource td { background-color:#FFFFFF; }
                table.dBug_resource td.dBug_resourceHeader { background-color:#AA66AA; }
                table.dBug_resource td.dBug_resourceKey { background-color:#FFDDFF; }

                /* xml */
                table.dBug_xml { background-color:#888888; }
                table.dBug_xml td { background-color:#FFFFFF; }
                table.dBug_xml td.dBug_xmlHeader { background-color:#AAAAAA; }
                table.dBug_xml td.dBug_xmlKey { background-color:#DDDDDD; }
            </style>
SCRIPTS;
    }
}
