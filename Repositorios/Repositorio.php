<?php namespace Repositorios;
use Utils;
use Models;

class Repositorio {
    protected $tabela;
    protected $wpdb;

    public function __construct(Utils\Tabela $tabela = null) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tabela = $tabela;
    }
    public function RetornaRegistros($criterios=null, $agrupamento=null, $ordenacao=null){
        $parametros = null;
        $sql = $this->RetornaSelectSQL($criterios, $agrupamento, $ordenacao, $parametros);
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

    public function RetornaRegistro($criterios=null, $agrupamento=null, $ordenacao=null){
        $registros = $this->RetornaRegistros($criterios, $agrupamento, $ordenacao);
        return $registros != null && !empty($registros)? $registros[0]: null;
    }

    public function InserirRegistro(Utils\Tabela $registro){
        $reflect = new \ReflectionClass($registro);
        if (!$registro->RetornaMapeamento()){
            return false;
        }

        $registro->AntesGravar();

        $nomeTabela = $registro->RetornaNomeTabelaMapeada();

        $sql = "INSERT INTO ".$nomeTabela;
        
        $colunas="";
        $parametros="";
        $valores = [];
        
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiro = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $this->RetornaColunaMapeada($propriedadeNome);
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
        if (!$registro->RetornaMapeamento()){
            return false;
        }

        $registro->AntesGravar();

        $nomeTabela = $registro->RetornaNomeTabelaMapeada();

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
            $coluna = $this->RetornaColunaMapeada($propriedadeNome);
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
        if (!$registro->RetornaMapeamento()){
            return false;
        }

        $nomeTabela = $registro->RetornaNomeTabelaMapeada();

        $sql = "DELETE FROM ".$nomeTabela;

        $condicoes="";
        $valores = [];
        
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiraChave = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $this->RetornaColunaMapeada($propriedadeNome);
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
	
    public function RetornaSelectSQL($criterios, $agrupamento, $ordenacao, &$parametros){
        $parametros = null;
        $sql = "";
        if (!$this->tabela->RetornaMapeamento()){
            return $sql;
        }
        
        $sql = "SELECT ";
        $reflect = new \ReflectionClass($this->tabela);
        $props   = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $primeiro = true;
        foreach ($props as $key => $prop) {
            $propriedadeNome = $prop->getName();
            $coluna = $this->RetornaColunaMapeada($propriedadeNome);
            if (isset($coluna)){                    
                $colunaNome = $coluna->nome;
                $sql = $sql.(!$primeiro?", ":"").$colunaNome.($colunaNome != $propriedadeNome? " ".$propriedadeNome:"");
                $primeiro = false;
            }
        }
        $nomeTabela = $this->tabela->RetornaNomeTabelaMapeada();

        $sql = $sql." FROM ".$nomeTabela;
        if (isset($criterios)){
            $parametros=[];
            if (is_array($criterios)){
                $primeiro = true;
                foreach ($criterios as $criterio) {
                    if (isset($coluna)){
                        $sql = $sql.($primeiro?" WHERE ":" AND ").$this->RetornaCondicaoSQL($criterio, $parametros);
                        $primeiro = false;
                    }    
                }                   
            }else {
                $sql = $sql." WHERE ".$this->RetornaCondicaoSQL($criterios, $parametros);
            }
        }
        if (isset($agrupamento)){
            $sql = $sql." GROUP BY";
            if (is_array($agrupamento)){
                $primeiro = true;
                foreach ($agrupamento as $agrupamentoCampo) {
                    $sql = $sql.(!$primeiro?", ": " ").$this->RetornaAgrupamentoSQL($agrupamentoCampo);
                    $primeiro = false;
                }
            }else {
                $sql = $sql." ".$this->RetornaAgrupamentoSQL($agrupamentoCampo);
            }
        }
        if (isset($ordenacao)){
            $sql = $sql." ORDER BY";
            if (is_array($ordenacao)){
                $primeiro = true;
                foreach ($ordenacao as $ordenacaoCampo) {
                    $sql = $sql.(!$primeiro?", ": " ").$this->RetornaOrdenacaoSQL($ordenacaoCampo);
                    $primeiro = false;
                }
            }else {
                $sql = $sql." ".$this->RetornaOrdenacaoSQL($ordenacaoCampo);
            }
        }
        return $sql;
    }
    protected function RetornaCondicaoSQL(Utils\Criterio $criterio, &$parametros){
        $operacao = $criterio->operacao;

        $primeiroOperador = "";
        if ($criterio->colunapropriedade){
            $coluna = $this->RetornaColunaMapeada($criterio->coluna);
            $primeiroOperador = $coluna->nome;
        } else {
            $primeiroOperador = $this->RetornaParametroPorTipo($criterio->coluna);
            $parametros[] = $criterio->coluna;
        }
        $segundoOperador = "";
        if ($criterio->valorpropriedade){
            $coluna = $this->RetornaColunaMapeada($criterio->valor);
            $segundoOperador = $coluna->nome;
        } else {
            $segundoOperador = $this->RetornaParametroPorTipo($criterio->valor);
            if ($criterio->valor === null){
                if ($operacao == "="){
                    $operacao = "IS";
                }else {
                    $operacao = "IS NOT";
                }
                $segundoOperador = "NULL";
            }else {
                $parametros[] = $criterio->valor;
            }
        }

        $sql = $primeiroOperador." ".$operacao." ".$segundoOperador;

        if (isset($criterio->juncao)){
            $sql = " ( ".$sql." ".$criterio->juncao." ".$this->RetornaCondicaoSQL($criterio->condicaojuncao, $parametros)." ) ";
        }
        return $sql;
    }
    protected function RetornaAgrupamentoSQL($agrupamento){
        if (is_array($agrupamento)){
            return $this->RetornaColunaMapeada($agrupamento[0])->nome." ".$agrupamento[1];
        }
        return $this->RetornaColunaMapeada($agrupamento)->nome;
    }
    protected function RetornaOrdenacaoSQL($ordenacao){
        if (is_array($ordenacao)){
            return $this->RetornaColunaMapeada($ordenacao[0])->nome." ".$ordenacao[1];
        }
        return $this->RetornaColunaMapeada($ordenacao)->nome;
    }            
    protected function RetornaParametro(Utils\TipoSQL $tipoSQL){
        $valorTipoSQL = $tipoSQL->RetornaValor();
        if ($valorTipoSQL == Utils\TipoSQL::Varchar()->RetornaValor() ||
            $valorTipoSQL == Utils\TipoSQL::Datetime()->RetornaValor()){
            return "%s";
        }else if ($valorTipoSQL == Utils\TipoSQL::Int()->RetornaValor()){
            return "%d";
        }else if ($valorTipoSQL == Utils\TipoSQL::Decimal()->RetornaValor()){
            return "%f";
        }else {
            return "";
        }        
    }
    protected function RetornaParametroPorTipo($variavel){
        //%d (integer) %f (float) %s (string)
        $tipoVariavel = gettype($variavel);
        if ($tipoVariavel == "integer"){
            return "%d";
        } else if ($tipoVariavel == "double" || $tipoVariavel == "float"){
            return "%f";
        } else {
            return "%s";
        }
    }
}