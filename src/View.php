<?php

namespace Webunion\View;

/**
 * A very simple and lightweight View engine framework agnostic.
 */
class View
{
    /**
     * Default template directory.
     *
     * @var Directory
     */
    private $path;

    /**
     * The content of the of the layout.
     *
     * @var Layout
     */
    private $layout;

    /**
     * Collection of preassigned Pages and Partial.
     *
     * @var Pages
     */
    private $pages = array();

    /**
     * Collection of preassigned template data.
     *
     * @var Data
     */
    private $data = array();

    /**
     * Collection of preassigned template CONSTANTS to be replaced in views/layouts {#VAR#}.
     *
     * @var FixData
     */
    private $fixData = array();

    /**
     * Define if the output should be compressed (remove HTML spaces, break lines adn comments).
     *
     * @var CompressOutput
     */
    private $compressOutput = false;

    /**
     * Create new View instance.
     *
     * @param string $path   the path wehre views and layout are placed
     * @param string $layout preload an layout
     * @param string $view   preload an view
     */
    public function __construct($path, $layout = null, $view = null)
    {
        $this->path = $path;
        $this->loadLayout($layout);
        $this->loadPage($view);
    }

    /**
     * Define if the output should be compressed.
     *
     * @param bool $option
     */
    public function setCompress($option = true)
    {
        $this->compressOutput = $option;
    }

    /**
     * Compress the output, removing HTML spaces, break lines and comments.
     *
     * @param string $content the HTML content to be compressed.
     *
     * @return string the HTML compressed content
     */
    private function compress($content)
    {
        return preg_replace(
                array('/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s', '#\s*<!--(?!\[if\s).*?-->\s*|(?<!\>)\n+(?=\<[^!])#s'),
                array('>', '<', '\\1', ''),
                $content);
    }

    /**
     * Load a layout content.
     *
     * @param string $file the relative address and name of the layout file.
     */
    public function loadLayout($file)
    {
        $file = $file ? $file : 'default';
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $file = $this->path.DIRECTORY_SEPARATOR.'layouts'.DIRECTORY_SEPARATOR.$file.'.php';
        if (is_file($file)) {
            $this->layout = file_get_contents($file);
        } else {
            throw new \Exception('Layout not found');
        }
    }

    /**
     * Load a view content.
     *
     * @param string $file the relative address and name of the layout file.
     */
    public function loadPage($file)
    {
        $file = $file ? $file : 'default';
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $file = $this->path.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$file.'.php';
        if (is_file($file)) {
            $this->pages['appPage'] = file_get_contents($file);
        } else {
            throw new \Exception('View not found');
        }
    }

    /**
     * Load a view content.
     *
     * @param string $name the name to be used like a array key in $this->pages.
     * @param string $file the relative address and name of the layout file.
     */
    public function loadPartial($name, $file)
    {
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $file = $this->path.DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$file.'.php';
        if (is_file($file)) {
            $this->pages[$name] = file_get_contents($file);
        } else {
            throw new \Exception('Partial not found');
        }
        $this->pages = array_reverse($this->pages);
    }

    /**
     * Alias to setVar.
     *
     * @param string|array $name    the variable name.
     * @param string       $content the variable content.
     * @param bool         $method
     */
    public function addData($name, $content = null, $method = false)
    {
        $this->setVar($name, $content, $method);
    }

    /**
     * Add preassigned template data.
     *
     * @param string|array $name    the variable name.
     * @param string       $content the variable content.
     * @param bool         $method
     */
    public function setVar($name, $content = null, $method = false)
    {
        if (!is_array($name)) {
            if (!$method) {
                $this->data[$name] = $content;
            } else {
                array_key_exists($name, $this->data) ? $this->data[$name] .= $content : $this->data[$name] = $content;
            }
        } else {
            foreach ($name as $key => $value) {
                if (is_null($method)) {
                    $this->data[$key] = $value;
                } else {
                    array_key_exists($key, $this->data) ? $this->data[$key] .= $value : $this->data[$key] = $value;
                }
            }
        }
    }

    /**
     * Alias to setFixVar.
     *
     * @param string|array $name    the variable name.
     * @param string       $content the variable content.
     * @param bool         $method
     */
    public function addFixData($name, $content = null, $method = false)
    {
        $this->setFixVar($name, $content, $method);
    }

    /**
     * Add preassigned template CONSTANT {#DATA#}.
     *
     * @param string|array $name    the variable name.
     * @param string       $content the variable content.
     * @param bool         $method
     */
    public function setFixVar($name, $content = null, $method = false)
    {
        if (!is_array($name)) {
            if (!$method) {
                $this->fixData[$name] = $content;
            } else {
                array_key_exists($name, $this->fixData) ? $this->fixData[$name] .= $content : $this->fixData[$name] = $content;
            }
        } else {
            foreach ($name as $key => $value) {
                if (is_null($method)) {
                    $this->fixData[$key] = $value;
                } else {
                    array_key_exists($key, $this->fixData) ? $this->fixData[$key] .= $value : $this->fixData[$key] = $value;
                }
            }
        }
    }

    /**
     * Replace FixVars markedwith {#VAR#} in layout and pages/partials.
     */
    public function replaceFixVars()
    {
        if (!empty($this->fixData) and is_array($this->fixData)) {
            foreach ($this->fixData as $key => $value) {
                $this->layout = str_replace('{#'.$key.'#}', $value, $this->layout);
                foreach ($this->pages as $k => $v) {
                    $this->pages[$k] = str_replace('{#'.$key.'#}', $value, $v);
                }
            }
        }
    }

    /**
     * Clear unused FixVars in layout and pages/partials.
     */
    protected function clearUnusedVars()
    {
        $this->layout = preg_replace('[{#(.*)#}]', '', $this->layout);

        foreach ($this->pages as $k => $v) {
            $this->pages[$k] = preg_replace('[{#(.*)#}]', '', $v);
        }
    }

    /**
     * Render layouts, page and partials to a string.
     *
     * @param string $page the relative address and name of the page file, if you didn't set it before.
     * @param array  $data add preassigned template data.
     *
     * @return string
     */
    public function render($page = null, array $data = array())
    {
        if (!is_null($page) && !empty($page)) {
            $this->loadPage($page);
        }

        //Substituo os dados armazenados em appViewfixData desta nos layout e view
        $this->replaceFixVars();

        //Transforma os dados passados no array deste metodo em variaveis locais
        if (!empty($data) and is_array($data)) {
            extract($data, EXTR_PREFIX_SAME, 'view');
        }

        //Transforma os dados armazenados em appViewData desta classe em variaveis locais
        if (!empty($this->data)) {
            extract($this->data, EXTR_PREFIX_SAME, 'view');
        }

        //Limpo variaveis que estao na view mas nao usadas (marcadas com tag)
        $this->clearUnusedVars();

        //renderizo o código php na view e salvo na variavel appPage

            foreach ($this->pages as $webunionViewKey => $webunionViewValue) {
                ob_start();
                eval('?>'.$webunionViewValue);
                ${$webunionViewKey} = ob_get_contents();
                ob_end_clean();
            }
        //renderizo o código php do layout (incluindo a view) e imprimo tudo
        ob_start();
        eval('?>'.$this->layout);
        $retorno = ob_get_contents();
        if ($this->compressOutput) {
            $retorno = $this->compress($retorno);
        }
        ob_end_clean();

        return $retorno;
    }

   //ENCODE AN ARRAY TO AN XML STRING
    public static function encodeXML($data, $node = null, $header = false, $att = '')
    {
        $xml = '';
        if (($header)) {
            $xml .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
        }
        $xml .= (!is_null($node) and !is_int($node)) ? '<'.trim($node.' '.$att).'>'."\n" : '';
        if (array_key_exists(0, $data)) {
            //$xml .= $data[0]."\n";
                //unset($data[0]);
        }
        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $att = '';
                if (array_key_exists('attr:', $val)) {
                    foreach ($val['attr:'] as $k => $v) {
                        $att .= $k.'="'.$v.'" ';
                    }
                    unset($val['attr:']);
                }
                if (count($val) == 0) {
                    $xml .= '<'.trim($key.' '.$att).' />'."\n";
                } else {
                    $xml .= self::encodeXML($val, $key, false, $att);
                }
                $att = '';
            } else {
                //PROBLEMA DO ZERO ESTÁ AQUI NO EMPTY
                    $xml .= ($val == '' || is_null($val)) ? '<'.trim($key.''.$att).' />'."\n" : "<$key>".htmlspecialchars($val)."</$key>\n";
            }
        }
        $xml .= (!is_null($node) and !is_int($node)) ? '</'.$node.'>'."\n" : '';

        return trim($xml);
    }

    //ENCODE AN ARRAY TO JSON STRING
    public static function encodeJSON($data, $node = false, $callBack = false)
    {
        $cIni = ($callBack) ?  '/**/'.$callBack.'('  : null;
        $cFim = ($callBack) ?  ');'  : null;

        if ($node) {
            $data = [$node => $data];
        }

        return $cIni.json_encode($data).$cFim;
    }
}
