# DB_PHPFramework
Pequeno framework em php para auxiliar na construção dos scripts SQL, por hora apenas na MySQL+WordPress(wpdb).

Exemplos de uso
$produto = new Models\Produto();
$repositorio = new Repositorios\Repositorio($produto);

$descricaoTeste = "%s%";
$criterio = Utils\Criterio::Condicao("descricao", "like", $descricaoTeste);
$criterio->Or($descricaoTeste, "=", "", false); //Para variavel = valor
$criterio->Or("descricao", "=", "descricao", true, true); //Para coluna = coluna
$criterio->Or("codigo", "=", "2");
$produtos = $repositorio->RetornaRegistros($criterio);

//Para inserir o registro
$produto->codigo="6";
$produto->descricao="TV 49 Polegadas";
$repositorio->InserirRegistro($produto);

//Para atualizar o registro
$produto->codigo="6";
$produto->descricao="TV 49 Polegadas";
$repositorio->AtualizarRegistro($produto);

//Para deletar o registro
$produto->codigo="6";
$repositorio->DeletarRegistro($produto);
