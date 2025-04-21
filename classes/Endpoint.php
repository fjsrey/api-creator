<?php
class Endpoint {
    public $url;
    public $method;
    public $input_mime;
    public $output_mime;
    public $script;
    public $return_text;
    public $scheme;

    public function __construct($url, $method, $input_mime, $output_mime, $script, $return_text, $scheme) {
        $this->url = $url;
        $this->method = $method;
        $this->input_mime = $input_mime;
        $this->output_mime = $output_mime;
        $this->script = $script;
        $this->return_text = $return_text;
        $this->scheme = $scheme;
    }

    // Getters
    public function getUrl() {
        return $this->url;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getInputMime() {
        return $this->input_mime;
    }

    public function getOutputMime() {
        return $this->output_mime;
    }

    public function getScript() {
        return $this->script;
    }

    public function getReturnText() {
        return $this->return_text;
    }

    public function getScheme() {
        return $this->scheme;
    }

    // Setters
    public function setUrl($url) {
        $this->url = $url;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function setInputMime($input_mime) {
        $this->input_mime = $input_mime;
    }

    public function setOutputMime($output_mime) {
        $this->output_mime = $output_mime;
    }

    public function setScript($script) {
        $this->script = $script;
    }

    public function setReturnText($return_text) {
        $this->return_text = $return_text;
    }

    public function setScheme($scheme) {
        $this->scheme = $scheme;
    }

    public function __toString() {
        return "URL: $this->url, Método: $this->method, Entrada: $this->input_mime, Salida: $this->output_mime, Script: $this->script, Valor predefinido: $this->return_text, Esquema: $this->scheme";
    }
}
?>
