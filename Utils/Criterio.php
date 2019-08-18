<?php namespace Utils;

class Criterio
{
    public $coluna;
    public $operacao;
    public $valor;
    public $colunapropriedade;
    public $valorpropriedade;
    
    public $juncao;
    public $condicaojuncao;

    public function Or($coluna, $operacao, $valor, $colunapropriedade = true, $valorpropriedade = false){
        return $this->Juncao('OR', $coluna, $operacao, $valor, $colunapropriedade, $valorpropriedade);
    }
    public function And($coluna, $operacao, $valor, $colunapropriedade = true, $valorpropriedade = false){
        return $this->Juncao('AND', $coluna, $operacao, $valor, $colunapropriedade, $valorpropriedade);
    }
    public function Juncao($juncao, $coluna, $operacao, $valor, $colunapropriedade = true, $valorpropriedade = false){
        $this->juncao = $juncao;
        $this->condicaojuncao = Criterio::Condicao($coluna, $operacao, $valor, $colunapropriedade, $valorpropriedade);
        return $this->condicaojuncao;
    }
    public static function Condicao($coluna, $operacao, $valor, $colunapropriedade = true, $valorpropriedade = false){
        $criterio = new Criterio();
        $criterio->coluna = $coluna;
        $criterio->operacao = $operacao;
        $criterio->valor = $valor;
        $criterio->colunapropriedade = $colunapropriedade;
        $criterio->valorpropriedade = $valorpropriedade;
        return $criterio;
    }
}

