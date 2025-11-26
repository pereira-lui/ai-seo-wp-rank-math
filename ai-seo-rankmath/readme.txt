=== AI SEO Assistant for Rank Math ===
Contributors: lui
Donate link: https://github.com/pereira-lui
Tags: seo, rank math, openai, ai, gpt, elementor, chatgpt, meta description, focus keyword
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Preenche automaticamente os campos de SEO do Rank Math (Title, Description, Focus Keyword) usando inteligência artificial da OpenAI.

== Description ==

**AI SEO Assistant for Rank Math** analisa o HTML renderizado da sua página e preenche automaticamente os campos de SEO do plugin Rank Math usando a API da OpenAI (ChatGPT).

Perfeito para quem usa **Elementor**, **Gutenberg** ou qualquer page builder, pois analisa a página final renderizada, não apenas o conteúdo do editor.

= ✨ Recursos Principais =

* 🔎 **Análise completa do HTML** - H1/H2, contagem de palavras, links, imagens sem ALT, Schema JSON-LD
* 🤖 **Geração via IA (GPT-4o-mini)** - Títulos, descrições e keywords otimizados para SEO
* ✍️ **Preenchimento inteligente** - Só preenche campos vazios, nunca sobrescreve edições manuais
* 🎯 **Atualização em tempo real** - Os campos do Rank Math são atualizados instantaneamente
* ⚡ **Compatível com Gutenberg e Editor Clássico**
* 🌐 **Open Graph** - Preenche título e descrição para Facebook/Twitter
* ⚙️ **Auto-aplicar** - Opção para executar automaticamente ao publicar

= 🔧 Como Funciona =

1. Configure sua API Key da OpenAI
2. Abra um post ou página no editor
3. Clique em "Analisar página e preencher" no metabox
4. Pronto! Os campos do Rank Math são preenchidos automaticamente

= 📋 Campos Preenchidos =

* **Title** (até 60 caracteres)
* **Meta Description** (até 160 caracteres com CTA)
* **Focus Keyword** (palavra-chave principal)
* **Slug** (sugestão de URL amigável)
* **Open Graph Title/Description** (para redes sociais)

= 🔐 Licenciamento =

Este plugin requer uma licença válida para uso contínuo. Novos usuários recebem **7 dias de trial grátis** para testar todas as funcionalidades.

== Installation ==

1. Baixe o arquivo ZIP do plugin
2. No WordPress, vá em **Plugins → Adicionar novo → Enviar plugin**
3. Selecione o arquivo ZIP e clique em **Instalar agora**
4. Ative o plugin
5. Vá em **Configurações → AI SEO (Rank Math)**
6. Ative sua licença e configure a API Key da OpenAI

= Configuração da API Key =

Você pode configurar a API Key de duas formas:

**Opção 1:** Nas configurações do plugin (Configurações → AI SEO)

**Opção 2:** No wp-config.php (mais seguro):
`define('OPENAI_API_KEY', 'sk-...');`

== Frequently Asked Questions ==

= Preciso ter o plugin Rank Math instalado? =

Sim, este plugin é um assistente para o Rank Math e preenche os campos de SEO dele.

= Qual modelo da OpenAI é usado? =

Usamos o GPT-4o-mini, que oferece excelente qualidade com custo reduzido.

= O plugin funciona com Elementor? =

Sim! O plugin analisa o HTML renderizado da página, então funciona perfeitamente com Elementor, Divi, WPBakery e qualquer page builder.

= Os campos existentes são sobrescritos? =

Não. O plugin só preenche campos que estão vazios. Se você já editou manualmente o título ou descrição, eles não serão alterados.

= Quanto custa usar a API da OpenAI? =

O custo é muito baixo. Com o GPT-4o-mini, cada análise custa aproximadamente $0.001-$0.005 dependendo do tamanho do conteúdo.

= Como obtenho uma licença? =

Acesse nossa página em https://github.com/pereira-lui/ai-seo-wp-rank-math para adquirir uma licença.

== Screenshots ==

1. Metabox no editor com botão de análise
2. Resultado da análise com sugestões da IA
3. Página de configurações com licença e API Key
4. Campos do Rank Math preenchidos automaticamente

== Changelog ==

= 2.0.0 =
* 🔐 Sistema de licenciamento para versão comercial
* 📦 Estrutura modular com classes organizadas
* 🔄 Atualização em tempo real dos campos do Rank Math
* ✨ Interface melhorada com feedback visual
* 🎯 Compatibilidade total com Gutenberg e Editor Clássico
* 📋 Brief/Contexto de SEO nas configurações
* 🔧 Período de trial de 7 dias

= 1.0.8 =
* Atualização em tempo real dos campos
* Interface melhorada no metabox
* Compatibilidade com Gutenberg e Editor Clássico

= 1.0.7 =
* Updater embutido via GitHub Releases
* Campo de Brief/Contexto de SEO

= 1.0.6 =
* Suporte a Open Graph (Facebook/Twitter)
* Sugestão de slug pela IA

= 1.0.5 =
* Botão "Testar chave agora"
* Normalização automática da API key

= 1.0.0 =
* Lançamento inicial

== Upgrade Notice ==

= 2.0.0 =
Nova versão comercial com sistema de licenciamento, atualização em tempo real e interface melhorada.
