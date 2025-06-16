curl -i -X PUT http://localhost:8000/api.php/contatos/1 ^
 -H "Content-Type: application/json" ^
 -d "{\"nome\":\"Novo Nome\",\"email\":\"novo@email.com\",\"assunto\":\"Atualizacao de contato\",\"cel\":\"0123456789\",\"mensagem\":\"Atualizando dados de teste.\"}"
pause
