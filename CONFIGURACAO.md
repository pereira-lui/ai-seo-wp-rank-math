# 📋 Guia de Configuração - AI SEO Assistant for Rank Math

## 🔑 Resumo das APIs

| API | Quem Configura | Onde Configurar | Propósito |
|-----|----------------|-----------------|-----------|
| **Asaas** | VOCÊ (vendedor) | Hardcoded no código ou wp-config.php do seu site de vendas | Receber pagamentos dos clientes |
| **OpenAI** | CLIENTE | Painel WordPress do cliente (Configurações → AI SEO) | Gerar conteúdo SEO com IA |

---

## 🏪 Configurando SUA Chave do Asaas (Pagamentos)

Esta chave é **sua** para receber os pagamentos. Configure **antes de distribuir o plugin**.

### Opção 1: Hardcoded (Recomendado para segurança)

Edite o arquivo `ai-seo-rankmath-pro/includes/class-asaas-integration.php`:

```php
private function get_api_key() {
    // Substitua pela sua chave real
    return '$aact_SuaChaveAsaasAqui';
}
```

### Opção 2: Via wp-config.php (para seu site de vendas)

```php
define('AI_SEO_RM_ASAAS_API_KEY', '$aact_SuaChaveAsaasAqui');
```

### Obter sua chave do Asaas:
1. Acesse https://www.asaas.com
2. Crie uma conta (se ainda não tiver)
3. Vá em **Integrações → API**
4. Copie sua API Key (começa com `$aact_`)

### Ambiente Sandbox (Testes):
Para testar, use o sandbox: https://sandbox.asaas.com
- Crie conta de testes
- Use a API Key do sandbox

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

## 🏷️ Planos Configurados

| Plano | Preço | Tipo |
|-------|-------|------|
| Mensal | R$ 29,90/mês | Recorrente |
| Anual | R$ 297,00/ano | Recorrente (2 meses grátis) |
| Vitalício | R$ 497,00 | Pagamento único |

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
