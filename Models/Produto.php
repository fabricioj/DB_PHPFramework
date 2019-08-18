<?php namespace Models;

use Utils;

class Produto extends Utils\Tabela {
    public $codigo;
    public $descricao;

    public function Mapeamento()
    {
        return [
            'Models\Produto' => new Utils\TabelaMap('produto'),
            'codigo'=> new Utils\ColunaMap('codigo', Utils\TipoSQL::VarChar(), true),
            'descricao'=> new Utils\ColunaMap('descricao', Utils\TipoSQL::VarChar())
        ];
    }
}
