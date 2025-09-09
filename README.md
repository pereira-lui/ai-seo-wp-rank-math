# AI SEO Assistant for Rank Math (Elementor + OpenAI)

Analisa o **HTML renderizado** da página (ótimo para Elementor) e preenche automaticamente os campos de SEO do **Rank Math** usando **OpenAI**. Inclui metabox no editor, botão **“Analisar página e preencher”**, **teste de chave**, normalização automática da API key e preenchimento opcional de Open Graph.

> **Compatível:** WordPress 5.8+, PHP 7.4+, Rank Math  
> **Requer:** OpenAI API Key

## Recursos
- 🔎 **Análise do HTML final** (permalink publicado): H1/H2, contagem de palavras, links internos/externos, imagens sem ALT, `<title>`, meta description e JSON-LD.
- 🤖 **Geração via IA (OpenAI)** com saída **100% JSON** (`response_format: json_object`) + parser robusto.
- ✍️ **Preenche Rank Math** (title, description, focus keyword) **apenas quando vazio** – não sobrescreve sua edição manual.
- 🧪 **Botão “Testar chave agora”** nas Configurações (detecta 401, mensagens da API, etc).
- 🔐 **Normalização da chave**: remove espaços/aspas/ocultos e extrai o primeiro `sk-...` caso você cole um bloco maior.
- 🧰 **Metabox** no editor com resumo técnico, sugestão da IA e relatório do que foi aplicado.
- ⚙️ **Auto-aplicar** na publicação/atualização (opcional).
- 🌐 **Open Graph** (Facebook/Twitter) se vazios.

## Instalação
1. Baixe o ZIP na aba **Releases**:  
   **Download direto:** `https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/latest/download/ai-seo-rankmath.zip`
2. WordPress → **Plugins → Adicionar novo → Enviar plugin**.
3. Ative.

## Configuração
- **Configurações → AI SEO (Rank Math)**: informe a OpenAI API Key ou defina no `wp-config.php`:
  ```php
  define('OPENAI_API_KEY', 'sk-...');
  ```
- Clique **“Testar chave agora”** para validar.

## Como usar
- Abra um post/página → metabox **AI SEO (Rank Math)** → **Analisar página e preencher**.
- Marque **“Aplicar automaticamente”** para rodar na publicação/atualização.

## Releases automáticas
Ao criar uma tag `v*` (ex.: `v1.0.7`), o GitHub Actions gera o ZIP automaticamente em **Releases** (workflow incluso em `.github/workflows/release.yml`).

## Atualizações automáticas no WordPress
Use **uma** das opções abaixo:

**A. Git Updater (simples):**
- Instale o plugin **Git Updater (Lite)** no WordPress.  
- Este plugin já inclui cabeçalhos:
  ```
  GitHub Plugin URI: pereira-lui/ai-seo-wp-rank-math
  Primary Branch: main
  ```

**B. (Opcional) Plugin Update Checker embutido:**
- Inclua a lib `YahnisElsts/plugin-update-checker` e ative no `ai-seo-rankmath.php` (exemplo comentado no arquivo).

## Licença
GPL-2.0
