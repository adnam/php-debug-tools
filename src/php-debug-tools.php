<?php
/**
 * Copyright (c) 2008-2013, Adam Hayward <adam@happy.cat>
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions 
 * are met:
 * 
 * - Redistributions of source code must retain the above copyright 
 *   notice, this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright 
 *   notice, this list of conditions and the following disclaimer in 
 *   the documentation and/or other materials provided with the distribution.
 * - Neither the name of the author nor the names of its contributors
 *   may be used to endorse or promote products derived from this software 
 *   without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED 
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, 
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, 
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR 
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF 
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING 
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
**/

define(PHP_DBG_TOOLS_START_TIME, microtime(true));

/**
 * Debugger
 */
class WssDebug
{

    protected static $debugger = null;

    var $debugFunctions = array('pr', 'dd', 'dump', 'debug', 'pr_cli', 'vd');

    /**
    * Html Class name given to div containing debug information
    */
    var $debugDivClass = 'wss-debug';

    /**
    * Singleton class
    */
    protected function __construct () {}
  
    static public function getDebugger ()
    {
        if (self::$debugger===null) {
            self::$debugger = new WssDebug();
        }
        return self::$debugger;
    }
  
    /**
     * Determine if script is being called via the command line or not
     */
    function isCLI ()
    {
        return strtolower(php_sapi_name())==='cli';
    }

    public function debug ($var = false, $showHtml = false, $showFrom = true)
    {
        if ($this->isCLI()){
            $this->debug_cli($var, false, $showFrom);
            return;
        }
        if ($showFrom) {
            $cwd = getcwd();
            $from = debug_backtrace();
            echo "<strong>".substr(str_replace($cwd, "", $from[0]['file']), 1)
                 . "</strong> (line <strong>" . $from[0]['line'] . "</strong>)";
        }
        echo "<pre>";
        if ($showHtml) {
            $var = str_replace(array('<','>'), array('&lt;','&gt;'), print_r($var, true));
            echo $var . '</pre>';
        }
        else {
            print_r($var);
            echo '</pre>';
        }
    }

    public function debug_cli ($var = false, $showHtml = false, $showFrom = true)
    {
        if ($showFrom) {
            $from = debug_backtrace();
            print substr(str_replace(ROOT, "", $from[0]['file']), 1)." (line ".$from[0]['line'].")";
        }
        print_r($var);
    }

    /**
     * Convenience function which prints out <pre> tags around the output 
     * of print_r called on the given variable. Similar to debug(), but 
     * with more bells and whistles. Also displays information about the
     * current execution point (file / line / class / method etc), time 
     * and memory usage.
     * 
     * If the variable is an exception, it will show the stack trace.
     * If the variable is an object it will call the method 'debug' if it 
     * exists in the object's class, rather than calling print_r on that 
     * object.
     *
     * @see   debug()
     * @see   dump()
     * @param mixed $var Variable to print out
     * @param boolean $escape If set to true, 
     * @param string $title An alternative title for the debugging output
     */
    public function pr ($var, $escape=false, $title=null)
    {
        if (!$this->isCli()) {
            $this->debug($this->getDebugHeader($var, $title), false, false);
        }
        $this->pr_pretty($var, $escape);
        if (!$this->isCli()) {
            echo $this->getDebugFooter();
        }
    }

    public function pr_many (/* multiple args */)
    {
        $args = func_get_args();
        $arg1 = array_shift($args);
        $prettyFunc = 'pr_pretty_cli';
        $separator = str_repeat('=', 80) . PHP_EOL;
        if (!$this->isCli()) {
            $this->debug($this->getDebugHeader($arg1), false, false);
            $prettyFunc = 'pr_pretty';
            $separator = '<hr />';
        }    
        $this->$prettyFunc($arg1, true);
        foreach ($args as $arg) {
            echo $separator;
            $this->$prettyFunc($arg, true);
        }
        if (!$this->isCli()) {
            echo $this->getDebugFooter();
        } else {
            echo $separator;
        }
    }

    function pr_pretty ($var, $escape=false)
    {
        if (is_object($var) && ($var instanceof WssDebug) && method_exists($var,'debug')) {
            $var->debug($trace);
        }
        elseif ($var===null) {
            debug('<span class="null">NULL</span>', false, false);
        }
        elseif ($var===true) {
            debug('<span class="true">TRUE</span>', false, false);
        }
        elseif ($var===false) {
            debug('<span class="false">FALSE</span>', false, false);
        }
        elseif (is_string($var)) {
            if (is_numeric($var)) {
                debug('<span class="numeric-string">' . $var . '</span><br/><small>(as string)</small>', false, false);
            }
            elseif (empty($var)) {
                debug('<span class="empty-string">EMPTY STRING</span>', false, false);
            }
            else {
                debug($var, $escape, false);
            }
        }
        elseif (is_numeric($var)) { // Numbers, non-strings
            debug('<span class="numeric">' . $var . '</span>', false, false);
        }
        else{
            debug($var, $escape, false);
        }
    }
    
    function pr_pretty_cli($var, $escape=false)
    {
        if (is_object($var) && ($var instanceof WssDebug) && method_exists($var,'debug')) {
            $var->debug($trace);
        }
        elseif ($var===null) {
            debug('<<< NULL >>>', false, false);
        }
        elseif ($var===true) {
            debug('<<< TRUE >>>', false, false);
        }
        elseif ($var===false) {
            debug('<<< FALSE >>>', false, false);
        }
        elseif (is_string($var)) {
            if (empty($var)) {
                debug('<<< EMPTY STRING >>>', false, false);
            }
            elseif (is_numeric($var)) {
                debug('<<< Number: ' . $var . ' >>> (as string)', false, false);
            }
            else {
                debug($var, $escape, false);
            }
        }
        elseif (is_numeric($var)) { // Numbers, non-strings
            debug('<<< ' . $var . ' >>>', false, false);
        }
        else{
            debug($var, $escape, false);
        }
    }
    
    function json_pretty($json)
    {
        $tab = "  ";
        $new_json = "";
        $indent_level = 0;
        $in_string = false;

        $json_obj = json_decode($json);

        if($json_obj === false) {
            return false;
        }

        $json = json_encode($json_obj);
        $len = strlen($json);

        for($c = 0; $c < $len; $c++) {
            $char = $json[$c];
            switch($char) {
            case '{':
            case '[':
                if(!$in_string){
                    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
                    $indent_level++;
                }
                else{
                    $new_json .= $char;
                }
                break;
            case '}':
            case ']':
                if(!$in_string){
                    $indent_level--;
                    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                }
                else{
                    $new_json .= $char;
                }
                break;
            case ',':
                if(!$in_string){
                    $new_json .= ",\n" . str_repeat($tab, $indent_level);
                }
                else{
                    $new_json .= $char;
                }
                break;
            case ':':
                if(!$in_string){
                    $new_json .= ": ";
                }
                else{
                    $new_json .= $char;
                }
                break;
            case '"':
                if($c > 0 && $json[$c-1] != '\\'){
                    $in_string = !$in_string;
                }
            default:
                $new_json .= $char;
                break;                   
            }
        }
        return $new_json;
    }

    function getPathHTML($path)
    {
        $parts = explode('/', $path);
        $numParts = count($parts);
        if (1 === $numParts) {
            return $path;
        }
        else {
            return "<acronym title=\"" . $path . "\">" . implode("/", array_slice($parts, -1)) . "</acronym>";
        }
    }

    function getDebugHeader ($var, $title=null)
    {
        $memory = memory_get_usage();
        $TIME_END = round(microtime(true) - PHP_DBG_TOOLS_START_TIME, 4) * 1000;
        if ($var instanceof Exception) {
            $trace = $var->getTrace();
            $title = '<strong style="color:#CC0000">' . get_class($var) . ' Exception:</strong> ' . $var->getMessage();
            $file = $this->getPathHTML($var->getFile());
            $line = $var->getLine();
        }
        else {
            $trace = $this->getBackTrace();
            if (isset($trace[0]['file'])){
                $file = $this->getPathHTML($trace[0]['file']);
            }
            else {
                $file = '<em>unknown file</em>';
            }
            if (isset($trace[0]['line'])){
                $line = $trace[0]['line'];
            }
            else {
                $line = '<em>unknown</em>';
            }
        }
        if ($memory < 1024){
            $memory = '<acronym title="' . $memory . ' b">' . $memory . '</acronym> B';
        }
        elseif ($memory > 1024 && $memory < 11048576){
            $memory = '<acronym title="' . $memory . ' b">' . round($memory/1024, 2) 
                    . '</acronym> KiB';
        }
        elseif ($memory > 11048576) {
            $memory = '<acronym title="' . $memory . ' b">' 
                    . round($memory/(11048576), 2) . '</acronym> MiB';
        }
        echo $this->getDebugCSS() . "\n";
        echo '<div class="' . $this->debugDivClass . '"><div class="' 
             . $this->debugDivClass . '-header">';
        $info = '';
        if ($title) $info .= '<h4>' . $title . '</h4>';
        $info .= 'Halting execution in: <b style="color:blue">' . $file . '</b> ';
        $info .= 'at line <b style="color:blue">' . $line . '</b> ';
        $info .= 'in function <b style="color:blue">';
        $tr = (isset($trace[1])) ? $trace[1] : $trace[0];
        if (isset($tr['class'])){
            $info .= $tr['class'] . $trace[1]['type'];
        }
        $info .= $tr['function'] . '()</b>. ';
        $info .= 'Execution time: <b style="color:blue">' . $TIME_END 
              . '</b> <acronym title="miliseconds">ms</acronym> using ' . $memory;
        $info .= '</div><div class="' . $this->debugDivClass . '-body">';
        return $info;
    }

    function getDebugFooter ()
    {
        return '</div></div>';
    }

    function getDebugCSS ()
    {
        static $sent = false;
        if ($sent) return null;
        $sent = true;
        $css = array();
        $css[] = ' {font-size: 11px; clear: both; border: 1px solid #aaa; '
               . 'border-top: 2px solid #aaa; background-color: #f5f5f5; '
               . 'margin:10px; padding: 0; overflow:auto} ';
        $css[] = '-header {clear: both; border: 0; background-color: #eee; '
               . 'padding: 10px; border-bottom: 1px solid #FF9933} ';
        $css[] = '-body {clear: both; border: 0; padding: 10px; } ';
        $css[] = ' .true  {color:#00c; font-weight: bold} ';
        $css[] = ' .false {color:#c00; font-weight: bold} ';
        $css[] = ' .null {color:#222; font-weight: bold} ';
        $css[] = ' .empty-string  {color:#222;} ';
        $css[] = ' .numeric-string, .wss-debug .numeric {color:#71103D;} ';
        return '<style type="text/css"><!-- '
               . '.' . $this->debugDivClass
               . implode('.' . $this->debugDivClass, $css)
               . '--></style>';
    }

    /**
     * Get a backtrace, but filter out entries related to debugging
     * (i.e. anything used in this file)
     */
    function getBackTrace ($trace=null)
    {
        if ($trace === null) {
            $trace = debug_backtrace();
        }
        $lastLine = null;
        $lastFile = null;
        foreach ($trace as $k=>&$v){
            if (!isset($v['file'])) $trace[$k]['file'] = $lastFile;
            if (!isset($v['line'])) $trace[$k]['line'] = $lastLine;
            $lastLine = $v['line'];
            $lastFile = $v['file'];
            switch (true) {
                case $v['file']==__FILE__:
                case (isset($v['class']) && $v['class']=='WssDebug'):
                #case (isset($v['function']) && in_array($v['function'], $this->debugFunctions)):
                    unset($trace[$k]);
                break;
            }
        }
        return array_values($trace);
    } 

    /**
     * Show debug infomation and halt execution.
     * 
     * @see   debug()
     * @param mixed $var Variable to print out
     * @param boolean $escape If set to true, 
     * @param string $title An alternative title for the debugging output
     */
    function dump ($var=null, $escape=false, $title=null)
    {
        pr($var, $escape, $title);
        die;
    }
    
    function jpr($json, $title=null)
    {
        pr($this->json_pretty($json), false, $title);
    }

}

function debug ($var = false, $showHtml = false, $showFrom = true) {
    WssDebug::getDebugger()->debug($var, $showHtml, $showFrom);
}
function dc ($var = false, $showHtml = false, $showFrom = true) {
    WssDebug::getDebugger()->debug_cli($var, $showHtml, $showFrom);
}
function pr ($var, $escape=false, $title=null) {
    WssDebug::getDebugger()->pr($var, $escape, $title);
}
function jpr ($var, $title=null) {
    WssDebug::getDebugger()->jpr($var, $title);
}
function dump ($var=null, $escape=false, $title=null){
    WssDebug::getDebugger()->dump($var, $escape, $title);
}
function dd () {
    $debugger = WssDebug::getDebugger();
    $args = func_get_args();
    call_user_func_array(array($debugger, 'pr_many'), $args);
}
function vd ($var) {
    $buf = ob_get_clean();
    ob_start();
    var_dump($var);
    $str = ob_get_contents();
    ob_end_clean();
    pr($str, true);
    if ($buf!==false) {
        ob_start();
        echo $buf;
    }
}
function desc($var) {
    if (is_object($var)) {
        $methods = array();
        foreach (get_class_methods($var) as $method) {
            $methods[] = "$method()";
        }
        $vars = get_object_vars($var);
        dd(
            get_class($var) . " object",
            $methods
        );
    } else {
        dd("Variable is a " . gettype($var));
    }
}
/**
 * cl - console log
 */
function cl ($str) {
    static $cli = null;
    if ($cli===null) {
        $cli = (strtolower(php_sapi_name())=='cli');
    }
    if ($cli===true){
        echo $str . "\n";
    }
    return;
}

function cnst($section=null)
{
    $all = get_defined_constants(true);
    if ($section===null) {
        return $all;
    }
    else {
        return $all[$section];
    }
}

