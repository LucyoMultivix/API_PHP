curl -i -X POST http://localhost:8000/api.php/contatos ^
 -H "Content-Type: application/json" ^
 -d "{\"nome\":\"Samara\",\"email\":\"tony@stark.com\",\"assunto\":\"Sete Dias\",\"cel\":\"11999999999\",\"mensagem\":\"Você tem 7 dias.\"}"
pause