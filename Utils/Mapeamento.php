<?php namespace Utils;

class TabelaMap{
    public $nome;
    
    public function __construct($nome) {
        $this->nome = $nome;
    }
}

class ColunaMap
{
    public $nome;
    public $tipo;
    public $chave;
    
    public function __construct($nome, $tipo, $chave=false) {
        $this->nome = $nome;
        $this->tipo = $tipo;
        $this->chave = $chave;
    }
}