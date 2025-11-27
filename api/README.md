# API de Licenças e Pagamentos - AI SEO PRO

Este diretório contém os arquivos necessários para gerenciar licenças e processar pagamentos do plugin AI SEO PRO.

## 📁 Arquivos

| Arquivo | Descrição |
|---------|-----------|
| `payment-proxy.php` | API que processa pagamentos via Asaas e valida licenças |
| `license-admin.php` | Painel administrativo para gerenciar licenças |
| `licenses.json` | Banco de dados de licenças (criado automaticamente) |
| `webhook.log` | Log dos webhooks recebidos do Asaas |

## 🚀 Instalação

### 1. Faça upload dos arquivos

Envie os arquivos para um servidor com PHP 7.4+:
```
https://seu-dominio.com/api/
├── payment-proxy.php
├── license-admin.php
└── (os outros arquivos são criados automaticamente)
```

### 2. Configure o payment-proxy.php

Edite o arquivo e configure:

```php
// Sua chave do Asaas
define('ASAAS_API_KEY', '$aact_SuaChaveAqui');

// Modo sandbox (true para testes, false para produção)
define('ASAAS_SANDBOX', false);

// Suas chaves mestra (para você e amigos)
define('MASTER_KEYS', [
    'MASTER-XXXX-XXXX-XXXX-XXXX',
    'MASTER-YYYY-YYYY-YYYY-YYYY',
]);
```

### 3. Configure o license-admin.php

Edite o arquivo e configure:

```php
// Senha para acessar o painel
define('ADMIN_PASSWORD', 'SuaSenhaForteAqui');

// Chave do Asaas (mesma do proxy)
define('ASAAS_API_KEY', '$aact_SuaChaveAqui');
```

### 4. Configure o Webhook no Asaas

1. Acesse o painel do Asaas
2. Vá em **Configurações** → **Integrações** → **Webhooks**
3. Adicione uma nova URL de webhook:
   ```
   https://seu-dominio.com/api/payment-proxy.php
   ```
4. Selecione os eventos:
   - `PAYMENT_CONFIRMED`
   - `PAYMENT_RECEIVED`
5. Salve

### 5. Configure o Plugin (versão FREE)

No arquivo `class-upgrade.php` do plugin FREE, configure a URL do proxy:

```php
$this->proxy_url = 'https://seu-dominio.com/api/payment-proxy.php';
```

## 🔐 Usando o Painel Admin

Acesse: `https://seu-dominio.com/api/license-admin.php`

### Gerar Licença Manual (para você ou amigos)

1. Faça login com a senha configurada
2. Selecione o tipo de chave:
   - **master**: Chave vitalícia que nunca expira
   - **manual**: Chave com prazo definido por você
3. Preencha nome e email (opcional)
4. Clique em "Gerar Chave"
5. Copie a chave gerada e envie para a pessoa

### Ver Todas as Licenças

O painel mostra todas as licenças com:
- Status (ativa, pendente, expirada)
- Tipo (pagamento, manual, master)
- Data de criação e expiração
- Nome e email do cliente

## 🔑 Como Funciona

### Fluxo de Compra

```
1. Cliente abre versão FREE → Clica em "Upgrade"
2. Plugin FREE envia dados para payment-proxy.php
3. Proxy cria cobrança no Asaas
4. Cliente paga (PIX, boleto ou cartão)
5. Asaas envia webhook → Proxy ativa licença
6. Cliente usa chave no plugin PRO
```

### Validação de Licença

```
1. Plugin PRO envia chave para payment-proxy.php
2. Proxy verifica:
   - É chave mestra? → Válida sempre
   - Está no licenses.json? → Verifica status e expiração
3. Retorna: válida, expirada ou inválida
```

## 📋 Endpoints da API

### POST /payment-proxy.php

#### Criar Pagamento
```json
{
  "action": "create_payment",
  "name": "Nome do Cliente",
  "email": "cliente@email.com",
  "cpf": "12345678900",
  "plan": "monthly|yearly|lifetime",
  "payment_method": "PIX|BOLETO|CREDIT_CARD"
}
```

#### Verificar Pagamento
```json
{
  "action": "check_payment",
  "payment_id": "pay_xxxxxxxxxxxxx"
}
```

#### Validar Licença
```json
{
  "action": "validate_license",
  "license_key": "AISEO-XXXX-XXXX-XXXX-XXXX",
  "site_url": "https://site-do-cliente.com"
}
```

## ⚠️ Segurança

1. **NUNCA** compartilhe sua chave do Asaas
2. Use uma **senha forte** no painel admin
3. Mantenha os arquivos em HTTPS
4. O arquivo `licenses.json` deve ser protegido pelo servidor
5. Adicione no `.htaccess`:
   ```apache
   <Files "licenses.json">
     Order allow,deny
     Deny from all
   </Files>
   
   <Files "webhook.log">
     Order allow,deny
     Deny from all
   </Files>
   ```

## 🧪 Testando

1. Configure `ASAAS_SANDBOX = true` no proxy
2. Use as credenciais de sandbox do Asaas
3. Faça um pagamento teste
4. Verifique se a licença foi ativada no painel admin

## ❓ FAQ

**Como dar licença gratuita para mim?**
- Adicione a chave no array `MASTER_KEYS` do proxy

**Como dar licença para um amigo?**
- Acesse o painel admin e gere uma licença do tipo "master"

**Preciso de banco de dados?**
- Não! Tudo é armazenado em `licenses.json`

**Posso usar outro gateway?**
- Sim, mas precisará modificar o código do proxy
