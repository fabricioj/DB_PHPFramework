<?php namespace Utils;
abstract class Tabela {

    protected $propriedades_mapeadas;
    
    public function __construct() {
        $this->propriedades_mapeadas = $this->Mapeamento();
    }

    public function RetornaMapeamento(){
        return $this->propriedades_mapeadas;
    }
    public function CopiaRow($row){
        $reflect = new \ReflectionClass($this);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $propNome = $prop->getName();
            if ($row[$propNome]){
                $this->{$propNome} = $row[$propNome];
            }
        }
    }
    //"Andando de fusca por enquanto", não encontrei maneira clara para poder utilizar as anotações
    //Então utilizaremos essa função para declarar o mapeamento das propriedades as colunas do DB
    abstract protected function Mapeamento();
}

