<?php namespace Utils;
abstract class Tabela {

    protected $propriedades_mapeadas;
    
    public function __construct() {
        $this->propriedades_mapeadas = $this->Mapeamento();
    }
    
	public function RetornaMapeamento(){
        return $this->propriedades_mapeadas;
    }
	
	public function RetornaNomeTabelaMapeada(){
        $reflect = new \ReflectionClass($this);
        $nomeTabela = $reflect->getName();
        $colunasMapeamento = $this->RetornaMapeamento();

        if ($colunasMapeamento != null){
            $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
            if (isset($mapeamentoTabela)){
                $nomeTabela = $mapeamentoTabela->nome;
            }
        }

        return $nomeTabela;
    }
	
	public function RetornaNomeTabelaMapeada(){
        $reflect = new \ReflectionClass($this);
        $nomeTabela = $reflect->getName();
        $colunasMapeamento = $this->RetornaMapeamento();

        if ($colunasMapeamento != null){
            $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
            if (isset($mapeamentoTabela)){
                $nomeTabela = $mapeamentoTabela->nome;
            }
        }

        return $nomeTabela;
    }
    public function CopiaRow($row){
        if ($row == null || empty($row)){
            return false;
        }

        $reflect = new \ReflectionClass($this);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $prop) {
            $propNome = $prop->getName();
            if (array_key_exists($propNome, $row)){
                $this->{$propNome} = $row[$propNome];
            }
        }
    }
    //"Andando de fusca por enquanto", não encontrei maneira clara para poder utilizar as anotações
    //Então utilizaremos essa função para declarar o mapeamento das propriedades as colunas do DB
    abstract protected function Mapeamento();
}

