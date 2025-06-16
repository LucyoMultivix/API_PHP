<?php
//Tipo de corpo de resposta
header('Content-Type: application/json');

//Essa array foi usada na parte 1, e seria usada para integrar o backend com o 
//frontend (Mas não conseguimos levar isso adiante por conta dos outros trabalho, 
//então deixamos ela ai para pq tem um GET relacionado a ela kkkk)
$perfil = [
    'id' => 1,
    'nomeCompleto' => 'Lucyo Regini Belloti',
    'profissao' => 'Estudante de Sistemas de Informação',
    'celular' => '028999999999',
    'email' => 'exemplo@gmail.com',
    'senioridade' => 'Junior',
    'bio' => 'Programador por Hobby'
];


//ARMAZENAR ROTAS
//Caminho total
$caminho = parse_url($_SERVER['REQUEST_URI'],
PHP_URL_PATH);
//Metodo da requisição
$metodo = $_SERVER['REQUEST_METHOD'];
//Rota base de todas as outras rotas
$rotaBase = '/api.php';
//Rotas uteis a cada endpoint separadas
$rotaPerfil = $rotaBase . '/perfil';
$rotaContatos = $rotaBase . '/contatos';
$rotaReset = $rotaBase . '/reset';


//FUNÇÕES
//Usada no PUT e no POST para validar dados que serão alterados ou criados no contatos.json
function validarContato($dados, $isUpdate = false) {
    $erros = [];

    //No POST (criação), 'nome' e 'email' são obrigatórios, no update pode não ser, por isso 
    //adicionei a variável $isUpdate
    if (!$isUpdate || isset($dados['nome'])) {
        if (empty($dados['nome'])) {
            $erros['nome'] = 'Nome é obrigatório.';
        } elseif (!is_string($dados['nome']) || strlen($dados['nome']) > 100) {
            $erros['nome'] = 'Nome deve ser uma string de até 100 caracteres.';
        }
    }

    if (!$isUpdate || isset($dados['email'])) {
        if (empty($dados['email'])) {
            $erros['email'] = 'Email é obrigatório.';
        } elseif (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Formato de email inválido.';
        }
    }

    if (isset($dados['assunto']) && strlen($dados['assunto']) > 100) {
        $erros['assunto'] = 'Assunto deve ter no máximo 100 caracteres.';
    }

    if (isset($dados['cel']) && !is_string($dados['cel'])) {
        $erros['cel'] = 'Celular deve ser uma string.';
    }

    if (isset($dados['mensagem']) && strlen($dados['mensagem']) > 500) {
        $erros['mensagem'] = 'Mensagem deve ter no máximo 500 caracteres.';
    }

    return $erros;
}

//Carrega a lista de contatos do contatos.json
function carregarLista() {
    $arquivo = 'contatos.json';
    if (!file_exists($arquivo)) {
        return [];
    }
    $conteudo = file_get_contents($arquivo);
    return json_decode($conteudo, true) ?? [];
}

//Salva alterações feitas no contato.json
function salvarLista($lista) {
    $arquivo = 'contatos.json';
    //Adicionei esses argumentos extras no json_encode pq tava bugando os caracteres especiais
    $json = json_encode($lista, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($arquivo, $json);
}

//Mensagens do sistema e resposta http
function mensagem($codigo) {
    http_response_code($codigo);

    switch ($codigo) {
        case 404:
            jeko(["mensagem" => "ERRO 404: Recurso não encontrado. Verifique a Rota"]);
            exit;
            break;
        case 400:
            jeko(["mensagem" => "ERRO 400: Dados inválidos ou faltando."]);
            break;
        default:
            jeko(["mensagem" => "Código de erro não tratado."]);
            break;
    }
}

//Encurtar o codigo de echo json_encode kkkk
function jeko($argumento) {
    //Adicionei esses argumentos extras no json_encode pq também tava bugando os caracteres especiais
    echo json_encode($argumento, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

//Encurtar o json_decode
function jdeko() {
    return json_decode(file_get_contents('php://input'), true);
}

//Buscar item por ID na lista do contatos.json e mostrar o item
function buscaID ($ID) {
    $achou = false;
    $lista = carregarLista();

    foreach ($lista as $item) {
        if ($item['id'] === $ID && $item['ativo'] === true) {
            jeko($item);
            $achou = true;
            break;
        }
    }
    
    if ($achou === false) {
        mensagem(404);
    }
}

//Usado no POST para criar um novo ID para o novo item da lista contatos.json
//Antes eu usava um ID = tamanho da list + 1, só q vi que isso poderia dar bug
function gerarNovoID($lista) {
    $maiorID = 0;
    foreach ($lista as $item) {
        if ($item['id'] > $maiorID) {
            $maiorID = $item['id'];
        }
    }
    return $maiorID + 1;
}

//Novo item na lista de contatos feitos ao seu perfil
//Resolvi colocar q o ID é o mesmo que o indice dele na lista + 1, pq no SQL se não me engano
//quando eles usam DELETE no CRUD ele apenas desativa o ID ao invés de apaga-lo para fins
//jurídicos no futuro talvez, apesar de q isso pode pesar o data bank
function novoContato($dados) {
    $lista = carregarLista();

    $erros = validarContato($dados);
    if (!empty($erros)) {
        http_response_code(400);
        jeko(['mensagem' => 'Erro ao criar contato. Dados inválidos ou faltando.']);
        return;
    }

    //Pega os dados do POST e adiciona como valores das chaves desse novo contato
    $novoContato = [
        'ativo' => true,
        'id' => gerarNovoID($lista),
        'assunto' => $dados['assunto'] ?? '',
        'nome' => $dados['nome'],
        'email' => $dados['email'],
        'cel' => $dados['cel'] ?? '',
        'mensagem' => $dados['mensagem'] ?? ''
    ];

    //Adiciona o novo contato na lista e depois salva ela no contatos.json
    $lista[] = $novoContato;
    salvarLista($lista);
    http_response_code(201);
    jeko($novoContato);
}

//Atualiza os dados de um contato da lista de contatos.json baseado no ID dele
function atualizarContato($id, $dados) {
    $lista = carregarLista();
    $achou = false;

    foreach ($lista as $indice => $contato) {
        if ($contato['id'] === $id) {
            $achou = true;

            // Validar dados usando a função
            $erros = validarContato($dados, true);
            if (!empty($erros)) {
                http_response_code(400);
                jeko(['erros' => $erros]);
                return;
            }

            // Atualiza os dados (mantendo o id original e ativo original se não for alterado)
            $lista[$indice] = [
                'ativo' => $dados['ativo'] ?? $contato['ativo'],
                'id' => $contato['id'],
                'assunto' => $dados['assunto'] ?? $contato['assunto'],
                'nome' => $dados['nome'] ?? $contato['nome'],
                'email' => $dados['email'] ?? $contato['email'],
                'cel' => $dados['cel'] ?? $contato['cel'],
                'mensagem' => $dados['mensagem'] ?? $contato['mensagem'],
            ];

            salvarLista($lista);
            http_response_code(204);
            return;
        }
    }

    if (!$achou) {
        mensagem(404);
    }
}

//Mostra todos os itens no contatos.json, é tipo dar um SELECT * no SQL
function mostrarTudo() {
    $lista = carregarLista();
    $ativos = [];

    foreach ($lista as $contato) {
        if ($contato['ativo'] === true) {
            $ativos[] = $contato;
        }
    }

    if (empty($ativos)) {
        http_response_code(404); // ou 204 No Content se preferir
        jeko(['mensagem' => 'ERRO 404: Nenhum contato ativo encontrado.']);
        return;
    }

    jeko($ativos); 
}

//Desativa um contato na lista contatos.json baseado no ID dele ao invés de deleta-lo
function desativarContato($id) {
    $lista = carregarLista();
    $achou = false;

    foreach ($lista as $indice => $contato) {
        if ($contato['id'] === $id) {
            $lista[$indice]['ativo'] = false;
            salvarLista($lista);
            http_response_code(200);
            jeko(["mensagem" => "Contato desativado com sucesso."]);
            $achou = true;
            break;
        }
    }

    if (!$achou) {
        mensagem(404);
    }
}


//METODOS GET
//Mostra perfil
if ($caminho === $rotaPerfil && $metodo === 'GET') {
    jeko($perfil);
    return;
} 

//Mostra todos os contatos
if ($caminho === $rotaContatos && $metodo === 'GET') {
    mostrarTudo();
    return;
} 

//Mostrar um contato pelo id
if (preg_match('#^' . preg_quote($rotaBase, '#') . '/contatos/(\d+)$#', $caminho, $matches) && $metodo === 'GET') {
    $id = (int) $matches[1];
    buscaID($id);
    return;
}   


//METODOS POST
//Novo contato para a lista em contatos.json
if ($caminho === $rotaContatos && $metodo === 'POST') {
    $dados = jdeko();
    novoContato($dados);
    return;
}

//Reseta o json aos contatos iniciais (Usado só para facilitar os testes)
if ($caminho === $rotaReset && $metodo === 'POST') {
    $originais = file_get_contents('contatos_originais.json');
    $dados = json_decode($originais, true);

    if (is_array($dados)) {
        salvarLista($dados);
        http_response_code(200);
        echo json_encode(['mensagem' => 'Contatos restaurados com sucesso.']);
    } else {
        mensagem(400);
    }
    return;
}

//METODOS PUT
//Atualiza um contato na lista contatos.json baseado no ID dele
if (preg_match('#^' . preg_quote($rotaContatos, '#') . '/(\d+)$#', $caminho, $matches) && $metodo === 'PUT') {
    $id = (int) $matches[1];
    $dados = jdeko();
    atualizarContato($id, $dados);
    return;
}

//METODOS DELETE
//Desativa um contato na lista contatos.json baseado no ID dele
if (preg_match('#^' . preg_quote($rotaContatos, '#') . '/(\d+)$#', $caminho, $matches) && $metodo === 'DELETE') {
    $id = (int) $matches[1];
    desativarContato($id);
    return;
}

//Se nenhuma rota der o return para interromper o codigo, gera o 404, 
//ou seja, não houve rotas compativeis
mensagem(404);
?>
