# AI SEO Assistant for Rank Math

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-orange.svg)](LICENSE)

Plugin WordPress que preenche automaticamente os campos de SEO do **Rank Math** usando inteligência artificial da **OpenAI (ChatGPT)**.

Analisa o **HTML renderizado** da página (perfeito para Elementor e page builders) e gera títulos, descrições e keywords otimizados para SEO.

## ✨ Recursos

- 🔎 **Análise do HTML final** - H1/H2, contagem de palavras, links, imagens sem ALT, JSON-LD
- 🤖 **Geração via IA (GPT-4o-mini)** - Saída 100% JSON estruturado
- ✍️ **Preenchimento inteligente** - Só preenche campos vazios
- 🔄 **Atualização em tempo real** - Campos atualizados instantaneamente
- 🎯 **Compatível** com Gutenberg e Editor Clássico
- 🌐 **Open Graph** - Facebook/Twitter meta tags
- ⚙️ **Auto-aplicar** na publicação (opcional)
- 🔐 **Licenciamento** - Trial de 7 dias incluso

## 📦 Instalação

### Via GitHub Releases (Recomendado)

1. Baixe o ZIP: [**Download Última Versão**](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath.zip)
2. WordPress → **Plugins → Adicionar novo → Enviar plugin**
3. Ative o plugin
4. Configure em **Configurações → AI SEO (Rank Math)**

### Via Git (Desenvolvimento)

```bash
cd wp-content/plugins/
git clone https://github.com/pereira-lui/ai-seo-wp-rank-math.git
```

## ⚙️ Configuração

### 1. Licença

- Novos usuários: **7 dias de trial grátis**
- Para uso contínuo, adquira uma licença
- Formato: `AISEO-XXXX-XXXX-XXXX`

### 2. API Key da OpenAI

**Opção A:** Nas configurações do plugin

**Opção B:** No `wp-config.php` (mais seguro):
```php
define('OPENAI_API_KEY', 'sk-...');
```

### 3. Brief de SEO (Opcional)

Configure um contexto global para orientar a IA:
> "Foco: scooters elétricas em Passo Fundo, com atendimento no Brasil inteiro."

## 🚀 Como Usar

1. Abra um post/página no editor
2. Localize o metabox **AI SEO (Rank Math)** na sidebar
3. Clique em **"Analisar página e preencher"**
4. Os campos do Rank Math serão preenchidos automaticamente

## 📋 Campos Preenchidos

| Campo | Limite | Descrição |
|-------|--------|-----------|
| Title | 60 chars | Título otimizado para SEO |
| Description | 160 chars | Meta descrição com CTA |
| Focus Keyword | - | Palavra-chave principal |
| Slug | - | URL amigável sugerida |
| OG Title | - | Título para redes sociais |
| OG Description | - | Descrição para redes sociais |

## 🔧 Desenvolvimento

### Estrutura do Projeto

```
ai-seo-wp-rank-math/
├── .github/
│   └── workflows/
│       └── release.yml          # GitHub Actions para releases
├── ai-seo-rankmath/             # Pasta do plugin
│   ├── includes/
│   │   └── class-license-manager.php
│   ├── vendor/
│   │   └── mini-puc/            # Updater para GitHub
│   ├── admin.js                 # JavaScript do admin
│   ├── ai-seo-rankmath.php      # Arquivo principal
│   └── readme.txt               # Readme para WordPress.org
├── CHANGELOG.md                 # Histórico de versões
├── LICENSE                      # GPL-2.0
├── README.md                    # Este arquivo
└── update.json                  # Metadata para updates
```

### Criando uma Release

1. Atualize a versão em:
   - `ai-seo-rankmath.php` (header e constante)
   - `readme.txt`
   - `update.json`
   - `CHANGELOG.md`

2. Commit e push:
```bash
git add .
git commit -m "Release v2.0.0"
git push origin main
```

3. Crie a tag:
```bash
git tag v2.0.0
git push origin v2.0.0
```

4. O GitHub Actions criará automaticamente o Release com o ZIP

### Atualizações Automáticas

O plugin verifica automaticamente novas versões no GitHub Releases e oferece atualização nativa no WordPress.

## 📄 Licença

GPL-2.0 - Veja [LICENSE](LICENSE) para detalhes.

## 🤝 Suporte

- [Issues](https://github.com/pereira-lui/ai-seo-wp-rank-math/issues)
- [Releases](https://github.com/pereira-lui/ai-seo-wp-rank-math/releases)

---

**Desenvolvido por [Lui](https://github.com/pereira-lui)**
