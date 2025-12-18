<?php
if(!defined("DS_FORM_LOAD") || DS_FORM_LOAD!==true) die();
/**
* 
*/
class DSMain
{
	const DSVERSION = '4.0.2';
	
    public $get = array();
    public $post = array();
    public $cookie = array();
    public $files = array();
    public $server = array();
    protected $formConfig;
    protected $formID;

    public $request;

    public function __construct() {
        $this->request();
    }

    protected function getConfig() {
        $this->formConfig = new DSConfig;
    }

    protected function request() {
        $this->get = $this->clean($_GET);
        $this->post = $this->clean($_POST);
        $this->request = $this->clean($_REQUEST);
        $this->cookie = $this->clean($_COOKIE);
        $this->files = $this->clean($_FILES);
        $this->server = $this->clean($_SERVER);
    }

    private function clean($data) {

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);
                $data[$this->clean($key)] = $this->clean($value);
            }
        } else {
            $data = htmlspecialchars($data, ENT_NOQUOTES, 'UTF-8');
        }

        return $data;
    }

    protected function responseJson($data = array()) {
        $result = $this->jsonEncode($data);
        echo($result);
    }

    protected function renderTemplate($template, $data = array()) {
        extract($data);
        unset($data);
        ob_start();
            if (file_exists(DS_FORM_ROOT . '/template/' . $this->formID . '/' . $template . '.php')) {
                include DS_FORM_ROOT . '/template/' . $this->formID . '/' . $template . '.php';
            } else {
                include DS_FORM_ROOT . '/template/default/' . $template . '.php';
            }
            $renderTemplate = ob_get_contents();
        ob_end_clean();
        return $renderTemplate;
    }

    public function jsonEncode($data = array()) {
        if(function_exists('json_encode')) {
            return json_encode($data);
        } else {
            switch (gettype($data)) {
                case 'boolean':
                    return $data ? 'true' : 'false';
                case 'integer':
                case 'double':
                    return $data;
                case 'resource':
                case 'string':
                    $json = '';

                    $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';

                    for ($i = 0; $i < strlen($string); $i++) {
                        $char = $string[$i];
                        $c1 = ord($char);

                        if ($c1 < 128) {
                            $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);

                            continue;
                        }

                        $c2 = ord($string[++$i]);

                        if (($c1 & 32) === 0) {
                            $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);

                            continue;
                        }
                        $c3 = ord($string[++$i]);

                        if (($c1 & 16) === 0) {
                            $json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128));

                            continue;
                        }
                        
                        $c4 = ord($string[++$i]);

                        if (($c1 & 8 ) === 0) {
                            $u = (($c1 & 15) << 2) + (($c2 >> 4) & 3) - 1;

                            $w1 = (54 << 10) + ($u << 6) + (($c2 & 15) << 2) + (($c3 >> 4) & 3);
                            $w2 = (55 << 10) + (($c3 & 15) << 6) + ($c4 - 128);

                            $json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
                        }
                    }

                    return $json;
                case 'array':
                    if (empty($data) || array_keys($data) === range(0, sizeof($data) - 1)) {
                        $output = array();

                        foreach ($data as $value) {
                            $output[] = $this->jsonEncode($value);
                        }

                        return '[' . implode(',', $output) . ']';
                    }
                case 'object':
                    $output = array();

                    foreach ($data as $key => $value) {
                        $output[] = $this->jsonEncode(strval($key)) . ':' . $this->jsonEncode($value);
                    }

                    return '{' . implode(',', $output) . '}';
                default:
                    return 'null';
            }
        }
    }

    public function jsonDecode($data = '') {
        if (get_magic_quotes_gpc()) {
            $data = stripslashes($data);
        }

        if(function_exists('json_decode')) {
            $dataDecode = json_decode($data, true);
        } else {
            $data = substr($data, 1, -1);
            $data = str_replace(array(":", "{", "[", "}", "]"), array("=>", "array(", "array(", ")", ")"), $data);
            @eval("\$dataDecode = array({$data});");
        }

        return $dataDecode;
    }

    private function compressCss($filename)
    {
        $css = file_get_contents($filename);
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css);
        return $css;
    }

    private function globCss($path="") {
        $css = '';
        foreach (glob(DS_FORM_ROOT . "/css/" . $path . "*.css") as $filename) {
            if(!preg_match('|/css/.*/_|siU', $filename)) {
                $css .= '/*path:' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $filename) . '*/' . "\n";
                $css .= $this->compressCss($filename) . "\n";
            }
        }
        return $css;
    }

    public function getCss() {
        header("Content-type: text/css; charset: UTF-8");
        $formCSS  = '';
        $formCSS .= $this->globCSS();
        $formCSS .= $this->globCSS('forms/');
        $formCSS .= $this->globCSS('plugins/');
        die($formCSS);
    }

    static public function routing() {

        $main   = new DSMain;
        $class  = 'DSMain';
        $class  = (isset($main->get['route']) && !empty($main->get['route']))? $main->get['route'] : $class;
        $class  = (isset($main->post['route']) && !empty($main->post['route']))? $main->post['route'] : $class;

        $method = (isset($main->get['m']) && !empty($main->get['m']))? $main->get['m'] : '';
        $method = (isset($main->post['m']) && !empty($main->post['m']))? $main->post['m'] : $method;

        if($class  == 'DSMain' && empty($method)) return false;

        try {

            $obj = new $class;

            if (!empty($method)) {
                $obj->$method();
            } else {
                $obj->index();
            }

        } catch (Exception $e) {
            $main->responseJson(
                    array(
                        'error'      => 3,
                        'error_text' => "Error: {$e->getMessage()}",
                    )
            );
        }

        return true;
    }
}
