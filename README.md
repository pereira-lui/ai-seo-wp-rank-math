# AI SEO Assistant for Rank Math

[![Version](https://img.shields.io/badge/version-2.1.0-blue.svg)](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](LICENSE)

Plugin WordPress que preenche automaticamente os campos de SEO do **Rank Math** usando inteligência artificial da **OpenAI (ChatGPT)**.

## 📦 Duas Versões Disponíveis

| Versão | Descrição | Onde obter |
|--------|-----------|------------|
| **FREE** | Análise técnica de SEO | [WordPress.org](https://wordpress.org/plugins/) |
| **PRO** | Preenchimento automático com IA | [GitHub Releases](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases) |

## ✨ Comparativo de Funcionalidades

| Funcionalidade | FREE | PRO |
|----------------|:----:|:---:|
| Análise técnica (H1, H2, palavras, links) | ✅ | ✅ |
| Detecção de imagens sem ALT | ✅ | ✅ |
| Verificação de Schema JSON-LD | ✅ | ✅ |
| Quick tips de SEO | ✅ | ✅ |
| **Geração de Title/Description com IA** | ❌ | ✅ |
| **Preenchimento automático do Rank Math** | ❌ | ✅ |
| **Auto-aplicar ao publicar** | ❌ | ✅ |
| **Open Graph (Facebook/Twitter)** | ❌ | ✅ |
| **Brief/Contexto de SEO** | ❌ | ✅ |
| **Pagamento integrado (Asaas)** | ❌ | ✅ |

## 🚀 Versão PRO

### Instalação

1. Baixe o ZIP: [**Download PRO**](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath-pro.zip)
2. WordPress → **Plugins → Adicionar novo → Enviar plugin**
3. Ative e configure em **Configurações → AI SEO (Rank Math)**

### Configuração

```php
// wp-config.php

// API Key da OpenAI (obrigatório)
define('OPENAI_API_KEY', 'sk-...');

// API Key do Asaas para pagamentos (opcional)
define('AI_SEO_RM_ASAAS_API_KEY', 'sua_chave');
```

### Planos e Preços

| Plano | Preço | Ciclo |
|-------|-------|-------|
| Mensal | R$ 29,90/mês | Recorrente |
| Anual | R$ 297,00/ano | Recorrente (2 meses grátis) |
| Vitalício | R$ 497,00 | Pagamento único |

## 📁 Estrutura do Projeto

```
ai-seo-wp-rank-math/
├── ai-seo-rankmath-free/    # Versão gratuita (WordPress.org)
│   ├── admin.js
│   ├── ai-seo-rankmath.php
│   └── readme.txt
│
├── ai-seo-rankmath-pro/     # Versão paga (GitHub)
│   ├── includes/
│   │   ├── class-license-manager.php
│   │   ├── class-asaas-integration.php
│   │   └── purchase-page.php
│   ├── vendor/
│   │   └── mini-puc/
│   ├── admin.js
│   ├── ai-seo-rankmath.php
│   └── readme.txt
│
├── .github/workflows/       # GitHub Actions
├── CHANGELOG.md
├── README.md
└── LICENSE
```

## 🔧 Desenvolvimento

### Criando uma Release

```bash
# Atualizar versões nos arquivos
# Commit
git add .
git commit -m "v2.2.0: Descrição"
git push origin main

# Criar tag
git tag v2.2.0
git push origin v2.2.0
```

O GitHub Actions criará automaticamente:
- `ai-seo-rankmath-free.zip` (para WordPress.org)
- `ai-seo-rankmath-pro.zip` (para venda)

## 📄 Licença

GPL-2.0 - Veja [LICENSE](LICENSE)

---

**Desenvolvido por [Lui](https://github.com/pereira-lui)**
