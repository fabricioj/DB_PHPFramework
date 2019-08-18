<?php namespace Utils;

abstract class Dominio
{
    private $valores = null;
    private $valor = null;

    public function __construct($valor = null) {
        $this->Inicializar();
        $this->valor = $valor;
    }

    abstract public function Inicializar();
    
    public function RetornaDescricao(){
        return isset($this->valor) && isset($this->valores)? $this->valores[$this->valor]: null;
    }

    public function RetornaValor(){
        return $this->valor;
    }
}


class TipoSQL extends Dominio
{
    public function __construct($valor = null){
        parent::__construct($valor);
    }
    public function Inicializar(){
        $this->valores = ['Varchar' => 'Varchar',
        'Int' => 'Int',
        'DateTime' => 'DateTime'];
    }

    public static function Varchar(){ return new TipoSQL('Varchar'); }
    public static function Int(){ return new TipoSQL('Int'); }
    public static function DateTime(){ return new TipoSQL('DateTime'); }
}


