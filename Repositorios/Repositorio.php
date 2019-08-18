<?php namespace Repositorios;
use Utils;

class Repositorio {
    protected $tabela;
    protected $wpdb;

    public function __construct(Utils\Tabela $tabela = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabela = $tabela;
    }
    public function RetornaRegistros($criterios){    
        $parametros = null;
        $sql = $this->RetornaSelectSQL($criterios, $parametros);
        $rows = null;
        if (!$parametros){
            $rows = $this->wpdb->get_results($sql, ARRAY_A);
        }else {
            $rows = $this->wpdb->get_results($this->wpdb->prepare($sql, $parametros), ARRAY_A);
        }

        $registros = [];
        $reflect = new \ReflectionClass($this->tabela);
        foreach ($rows as $row) {
            $registro = $reflect->newInstance();
            $registro->CopiaRow($row);
            $registros[] = $registro;
        }
        return $registros;

    }

    public function InserirRegistro(Utils\Tabela $registro){
        $reflect = new \ReflectionClass($registro);
        $colunasMapeamento = $registro->RetornaMapeamento();
        if (!isset($colunasMapeamento)){
            return false;
        }

        $nomeTabela = $reflect->getName();
        $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
        if (isset($mapeamentoTabela)){
            $nomeTabela = $mapeamentoTabela->nome;
        }

        $sql = "INSERT INTO ".$nomeTabela;

        $colunas="";
        $parametros="";
        $valores = [];
        
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiro = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $colunasMapeamento[$propriedadeNome];
            $valor = $prop->getValue($registro);
            if (isset($coluna) && isset($valor)){
                $colunas = $colunas.(!$primeiro?", ":"").$coluna->nome;
                $parametros = $parametros.(!$primeiro?", ":"").$this->RetornaParametro($coluna->tipo);
                $valores[] = $valor;
                $primeiro = false;
            }
        }
        if (empty($valores)){
            return false;
        }

        $sql = $sql." (".$colunas.") VALUES (".$parametros.")";
        $this->wpdb->query($this->wpdb->prepare($sql, $valores));
        $resultado = $this->wpdb->last_result;
        
        $this->wpdb->flush();
        return $resultado;
        
    }

    public function AtualizarRegistro(Utils\Tabela $registro){
        $reflect = new \ReflectionClass($registro);
        $colunasMapeamento = $registro->RetornaMapeamento();
        if (!isset($colunasMapeamento)){
            return false;
        }

        $nomeTabela = $reflect->getName();
        $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
        if (isset($mapeamentoTabela)){
            $nomeTabela = $mapeamentoTabela->nome;
        }

        $sql = "UPDATE ".$nomeTabela;

        $colunas="";
        $condicoesChave="";
        $valores = [];
        $valoresChaves = [];
        
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiraColuna = true;
        $primeiraChave = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $colunasMapeamento[$propriedadeNome];
            $valor = $prop->getValue($registro);
            if (isset($coluna)){
                if (!$coluna->chave){
                    $colunas = $colunas.($primeiraColuna?"SET ":", ").$coluna->nome." = ".$this->RetornaParametro($coluna->tipo);
                    $valores[] = $valor;
                    $primeiraColuna = false;
                }else {
                    $condicoesChave = $condicoesChave.($primeiraChave?"WHERE ":"AND ").$coluna->nome." = ".$this->RetornaParametro($coluna->tipo);
                    $valoresChaves[] = $valor;
                    $primeiraChave = false;
                }
            }
        }
        if (empty($valores)){
            return false;
        }
        
        $valores = array_merge($valores, $valoresChaves);

        $sql = $sql." ".$colunas." ".$condicoesChave;
        $this->wpdb->query($this->wpdb->prepare($sql, $valores));

        $resultado = $this->wpdb->last_result;
        $this->wpdb->flush();
        return $resultado;
    }

    public function DeletarRegistro(Utils\Tabela $registro){
        $reflect = new \ReflectionClass($registro);
        $colunasMapeamento = $registro->RetornaMapeamento();
        if (!isset($colunasMapeamento)){
            return false;
        }

        $nomeTabela = $reflect->getName();
        $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
        if (isset($mapeamentoTabela)){
            $nomeTabela = $mapeamentoTabela->nome;
        }

        $sql = "DELETE FROM ".$nomeTabela;

        $condicoes="";
        $valores = [];
        
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiraChave = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $colunasMapeamento[$propriedadeNome];
            $valor = $prop->getValue($registro);
            if (isset($coluna) && $coluna->chave){
                $sql = $sql.($primeiraChave?" WHERE ":" AND ").$coluna->nome." = ".$this->RetornaParametro($coluna->tipo);
                $valores[] = $valor;
                $primeiraChave = false;                
            }
        }
        if (empty($valores)){
            return false;
        }

        $this->wpdb->query($this->wpdb->prepare($sql, $valores));

        $resultado = $this->wpdb->last_result;
        $this->wpdb->flush();
        return $resultado;
    }

    public function RetornaSelectSQL($criterios, &$parametros){
        $parametros = null;
        $sql = "";
        $colunasMapeamento = $this->tabela->RetornaMapeamento();
        if (!isset($colunasMapeamento)){
            return $sql;
        }
        
        $sql = "SELECT ";
        $reflect = new \ReflectionClass($this->tabela);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiro = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $colunasMapeamento[$propriedadeNome];
            if (isset($coluna)){                    
                $colunaNome = $coluna->nome;
                $sql = $sql.(!$primeiro?", ":"").$colunaNome.($colunaNome != $propriedadeNome? " ".$propriedadeNome:"");
                $primeiro = false;
            }
        }
        $nomeTabela = $reflect->getName();
        $mapeamentoTabela = $colunasMapeamento[$nomeTabela];
        if (isset($mapeamentoTabela)){
            $nomeTabela = $mapeamentoTabela->nome;
        }
        $sql = $sql." FROM ".$nomeTabela;
        if (isset($criterios)){
            $parametros=[];
            if (is_array($criterios)){
                foreach ($criterios as $criterio) {
                    $primeiro = true;
                    if (isset($coluna)){
                        $sql = $sql.($primeiro?" WHERE ":" AND ").$this->RetornaCondicaoSQL($criterio, $parametros);
                        $primeiro = false;
                    }    
                }                   
            }else {
                $sql = $sql." WHERE ".$this->RetornaCondicaoSQL($criterios, $parametros);
            }
        }
        return $sql;
    }
    private function RetornaCondicaoSQL(Utils\Criterio $criterio, &$parametros){
        $colunasMapeamento = $this->tabela->RetornaMapeamento();
        $primeiroOperador = "";
        if ($criterio->colunapropriedade){
            $primeiroOperador = $colunasMapeamento[$criterio->coluna]->nome;
        } else {
            $primeiroOperador = $this->RetornaParametroPorTipo($criterio->coluna);
            $parametros[] = $criterio->coluna;
        }
        $segundoOperador = "";
        if ($criterio->valorpropriedade){
            $segundoOperador = $colunasMapeamento[$criterio->valor]->nome;
        } else {
            $segundoOperador = $this->RetornaParametroPorTipo($criterio->valor);
            $parametros[] = $criterio->valor;
        }

        $sql = $primeiroOperador." ".$criterio->operacao." ".$segundoOperador;

        if (isset($criterio->juncao)){
            $sql = " ( ".$sql." ".$criterio->juncao." ".$this->RetornaCondicaoSQL($criterio->condicaojuncao, $parametros)." ) ";
        }
        return $sql;
    }
    private function RetornaParametro(Utils\TipoSQL $tipoSQL){
        $valorTipoSQL = $tipoSQL->RetornaValor();
        if ($valorTipoSQL == Utils\TipoSQL::Varchar()->RetornaValor() ||
            $valorTipoSQL == Utils\TipoSQL::Datetime()->RetornaValor()){
            return "%s";
        }else if ($valorTipoSQL == Utils\TipoSQL::Int()->RetornaValor()){
            return "%d";
        }else {
            return "";
        }        
    }
    private function RetornaParametroPorTipo($variavel){
        //%d (integer) %f (float) %s (string)
        $tipoVariavel = gettype($variavel);
        if ($tipoVariavel == "integer"){
            return "%d";
        } else if ($tipoVariavel == "double"){
            return "%f";
        } else {
            return "%s";
        }
    }
}