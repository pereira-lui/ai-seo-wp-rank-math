# 📋 Guia de Configuração - AI SEO Assistant for Rank Math

## 🔑 Resumo das APIs

| API | Quem Configura | Onde Configurar | Propósito |
|-----|----------------|-----------------|-----------|
| **Asaas** | VOCÊ (vendedor) | Arquivo `payment-proxy.php` no SEU servidor | Receber pagamentos dos clientes |
| **OpenAI** | CLIENTE | Painel WordPress do cliente (Configurações → AI SEO) | Gerar conteúdo SEO com IA |

---

## 🔒 Sistema de Pagamento Seguro (API Proxy)

Sua chave do Asaas **NÃO fica no plugin**! Ela fica segura no seu servidor.

### Passo 1: Hospede o arquivo proxy

1. Pegue o arquivo `api/payment-proxy.php` deste repositório
2. Edite e coloque sua chave do Asaas:
   ```php
   define('ASAAS_API_KEY', '$aact_SuaChaveAqui');
   ```
3. Faça upload para qualquer hospedagem PHP:
   - Hostinger, Hostgator, Locaweb, etc.
   - Ou até uma hospedagem gratuita como InfinityFree
   - Exemplo: `https://seu-dominio.com/api/payment-proxy.php`

### Passo 2: Configure o plugin FREE

Edite `ai-seo-rankmath-free/includes/class-upgrade.php`:
```php
$this->proxy_url = 'https://seu-dominio.com/api/payment-proxy.php';
```

### Por que isso é seguro?

```
Plugin FREE (no cliente) → Seu Proxy → API Asaas
     ↓                        ↓
 Não tem chave          Tem a chave
   (seguro)              (seguro)
```

- O cliente nunca vê sua chave
- Sua chave fica só no seu servidor
- Você tem controle total

---

## 👤 Configuração do CLIENTE (OpenAI)

O cliente final precisa configurar sua própria chave da OpenAI para usar o plugin.

### Como o cliente configura:

1. Acessar o painel WordPress
2. Ir em **Configurações → AI SEO (Rank Math)**
3. Ativar a licença (que ele comprou de você)
4. Inserir a chave da OpenAI

### Onde obter:
- https://platform.openai.com/api-keys
- Criar conta na OpenAI
- Gerar uma API Key (`sk-...`)

### Custo para o cliente:
- OpenAI cobra por uso (tokens)
- Modelo `gpt-4o-mini` é bem econômico (~$0.01 por análise)
- O cliente paga conforme usa

---

## 🔔 Configurando o Webhook do Asaas

Para receber confirmações de pagamento automáticas:

1. Acesse seu painel Asaas
2. Vá em **Integrações → Webhooks**
3. Adicione um novo webhook:
   - **URL:** `https://SEU-SITE.com/wp-json/ai-seo-rm/v1/webhook/asaas`
   - **Eventos:** 
     - `PAYMENT_CONFIRMED`
     - `PAYMENT_RECEIVED`
     - `PAYMENT_OVERDUE`
     - `PAYMENT_REFUNDED`

---

## 💳 Métodos de Pagamento Disponíveis

- **PIX** - Pagamento instantâneo (mais usado no Brasil)
- **Boleto** - Vencimento em 3 dias
- **Cartão de Crédito** - Aprovação imediata

---

## 🏷️ Planos Configurados (Pagamento Único)

| Plano | Preço | Validade |
|-------|-------|----------|
| Mensal | R$ 29,90 | 30 dias |
| Anual | R$ 297,00 | 365 dias |
| Vitalício | R$ 497,00 | Para sempre |

**Todos são pagamentos únicos** - não há cobrança recorrente automática.

Para alterar os preços, edite `class-asaas-integration.php` na seção `$this->plans`.

---

## 📦 Distribuição

### Versão FREE (WordPress.org):
- Upload `ai-seo-rankmath-free.zip` em https://wordpress.org/plugins/developers/add/
- Não inclui IA, apenas análise técnica
- Gratuito para sempre

### Versão PRO (Seu site):
- Venda através do sistema de pagamentos integrado
- Inclui todas as funcionalidades com IA
- Cliente precisa de sua própria API Key da OpenAI

---

## ❓ FAQ

**P: O cliente precisa de conta no Asaas?**
R: Não! O Asaas é só seu para receber. O cliente só paga via PIX/Boleto/Cartão.

**P: O cliente precisa de conta na OpenAI?**
R: Sim! Cada cliente precisa de sua própria chave da OpenAI para usar a IA.

**P: Quanto custa para o cliente usar a OpenAI?**
R: Aproximadamente $0.01 por análise de página. Muito econômico.

**P: Posso mudar os preços?**
R: Sim! Edite o arquivo `class-asaas-integration.php`.
