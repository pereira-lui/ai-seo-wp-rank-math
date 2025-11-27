# Site AI SEO PRO

Este é o site de vendas do plugin AI SEO PRO.

## Estrutura

```
site/
├── index.html          # Landing page com checkout
├── api/
│   └── payment-proxy.php   # API de pagamento (Asaas)
```

## Configuração

1. Faça upload de toda a pasta `site/` para seu servidor
2. Configure a chave do Asaas no arquivo `api/payment-proxy.php`
3. Configure o webhook no Asaas para: `https://seu-dominio.com/api/payment-proxy.php`

## Testando localmente

Você pode usar PHP built-in server:

```bash
cd site
php -S localhost:8000
```

Acesse: http://localhost:8000

## Produção

Faça upload para: `https://ai-seo-wp-rank-math.it2.solutions/`

A estrutura ficará:
- https://ai-seo-wp-rank-math.it2.solutions/ (landing page)
- https://ai-seo-wp-rank-math.it2.solutions/api/payment-proxy.php (API)
