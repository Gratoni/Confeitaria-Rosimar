# Confeitaria Rosimar

Loja web em PHP para encomendas de bolos com fechamento via WhatsApp.

## Objetivo

Entregar um fluxo simples e seguro:

1. Cliente navega no cardapio.
2. Adiciona bolos no carrinho.
3. Finaliza no checkout.
4. Sistema gera mensagem pronta no WhatsApp.

## Arquitetura

- `index.php`: pagina principal, vitrine, filtros de catalogo e modal de produto.
- `checkout.php`: captura dados do cliente e confirma pedido.
- `carrinho.php`: API de sessao para adicionar/atualizar/remover itens.
- `db/config.php`: bootstrap de sessao, DB, CSRF, seguranca HTTP e seed de catalogo.
- `lib/catalog.php`: regras de catalogo (produtos padrao, imagem segura, categoria).
- `lib/cart.php`: regras de quantidade e limites de carrinho.
- `lib/validation.php`: sanitizacao e validacao de dados de checkout.
- `lib/whatsapp.php`: montagem de mensagem e URL de redirecionamento.
- `css/style.css` + `js/script.js`: interface, acessibilidade e interacoes.

## Seguranca aplicada

- Token CSRF nas operacoes sensiveis de carrinho e checkout.
- Cookies de sessao com `HttpOnly` e `SameSite=Lax`.
- Headers de hardening (`CSP`, `X-Frame-Options`, `X-Content-Type-Options`, etc.).
- Validacao de entrada no servidor para nome, telefone, observacoes e quantidade.
- Limites de quantidade por item e limite de itens distintos no carrinho.
- Sanitizacao de caminhos de imagem do catalogo para evitar referencias indevidas.

## Catalogo e imagens

O projeto usa imagens locais em `assets/bolos/*`.

O seed inicial adiciona bolos de festa e caseiros, incluindo:

- Chocolate
- Morango
- Cenoura caseiro
- Banana com canela
- Fuba com erva-doce
- Milho cremoso
- Limao
- Ninho com morango
- Variacoes caseiras adicionais

## Requisitos

- XAMPP (Apache + MySQL)
- PHP 8.0+ (recomendado 8.1+)

## Execucao local

1. Inicie Apache e MySQL no XAMPP.
2. Coloque a pasta em `htdocs`.
3. Abra `http://localhost/confeitaria_rosimar`.
4. O banco e tabelas sao criados automaticamente no primeiro acesso.

## Analytics (opcional)

Configure IDs GTM/GA4 em:

- `config/analytics.php`
- ou variaveis de ambiente: `GTM_CONTAINER_ID` e `GA4_MEASUREMENT_ID`

## Testes

No Windows com XAMPP:

```powershell
C:\xampp\php\php.exe tests/run.php
```

Arquivos:

- `tests/validation_test.php`
- `tests/integration_cart_test.php`
