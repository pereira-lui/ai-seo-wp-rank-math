# Changelog

Todas as mudanças notáveis do AI SEO Assistant for Rank Math serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Unreleased]

## [2.0.0] - 2025-11-26

### Adicionado
- 🔐 Sistema de licenciamento para versão comercial
- 📦 Estrutura modular com classes organizadas em `/includes/`
- 🔄 Atualização em tempo real dos campos do Rank Math (sem recarregar página)
- ✨ Interface melhorada no metabox com feedback visual
- 🎯 Compatibilidade total com Gutenberg e Editor Clássico
- 📋 Campo de Brief/Contexto de SEO nas configurações
- 🔧 Período de trial de 7 dias para novos usuários
- 📊 Página de status da licença no admin

### Melhorado
- Performance geral do plugin
- Tratamento de erros da API OpenAI
- Feedback visual durante análise
- Documentação e comentários no código

### Corrigido
- Campos do Rank Math não apareciam sem recarregar a página

## [1.0.8] - 2025-11-26

### Adicionado
- Atualização em tempo real dos campos do Rank Math
- Interface melhorada no metabox com feedback visual
- Compatibilidade com Gutenberg e Editor Clássico

### Corrigido
- Valores só apareciam após atualizar a página

## [1.0.7] - 2025-11-25

### Adicionado
- Updater embutido (mini-puc) para atualizações via GitHub Releases
- Campo de Brief/Contexto de SEO em Configurações
- Links para GitHub e Releases na linha do plugin

## [1.0.6] - 2025-11-24

### Adicionado
- Open Graph (Facebook/Twitter) title e description
- Suporte a slug sugerido pela IA

### Melhorado
- Prompt da IA com regras mais específicas
- Extração robusta de JSON da resposta

## [1.0.5] - 2025-11-23

### Adicionado
- Botão "Testar chave agora" nas configurações
- Normalização automática da API key

### Corrigido
- Tratamento de erro 401 da OpenAI

## [1.0.4] - 2025-11-22

### Adicionado
- Opção de auto-aplicar na publicação/atualização
- Análise de JSON-LD (Schema)

## [1.0.3] - 2025-11-21

### Melhorado
- Análise de HTML com DOMDocument
- Contagem de links internos/externos

## [1.0.2] - 2025-11-20

### Adicionado
- Detecção de imagens sem atributo ALT
- Quick tips baseados na análise

## [1.0.1] - 2025-11-19

### Corrigido
- Compatibilidade com PHP 7.4
- Encoding UTF-8 no HTML

## [1.0.0] - 2025-11-18

### Adicionado
- Lançamento inicial
- Análise do HTML renderizado da página
- Integração com API OpenAI (GPT-4o-mini)
- Preenchimento automático de Title, Description e Focus Keyword
- Metabox no editor de posts/páginas
- Configurações para API Key
- Suporte a constante OPENAI_API_KEY no wp-config.php

[Unreleased]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.8...v2.0.0
[1.0.8]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.7...v1.0.8
[1.0.7]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.6...v1.0.7
[1.0.6]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.5...v1.0.6
[1.0.5]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.4...v1.0.5
[1.0.4]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.3...v1.0.4
[1.0.3]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/pereira-lui/ai-seo-wp-rank-math/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/pereira-lui/ai-seo-wp-rank-math/releases/tag/v1.0.0
