<?php

header('Content-Type: application/json');

$perfil = [
    'id' => 1,
    'nomeCompleto' => 'Lucyo Regini Belloti',
    'profissao' => 'Estudante de Sistemas de Informação',
    'celular' => '028999999999',
    'email' => 'exemplo@gmail.com',
    'senioridade' => 'Junior',
    'bio' => 'Programador por Hobby'
];

$caminho = parse_url($_SERVER['REQUEST_URI'],
PHP_URL_PATH);
$metodo = $_SERVER['REQUEST_METHOD'];
$rotaBase = '/api.php';
$rotaPerfil = $rotaBase . '/perfil';

if ($caminho === $rotaPerfil && $metodo === 'GET') {
    echo json_encode($perfil);
} else {
    http_response_code(404);
    echo json_encode([
        "mensagem" => "Recurso não encontrado. Verifique a Rota"
    ]);
}
?>
