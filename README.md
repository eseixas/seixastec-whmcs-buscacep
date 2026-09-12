# SeixasTec BuscaCEP

Addon para **WHMCS 9** que valida o CEP brasileiro e preenche o endereço automaticamente.

Consulta **ViaCEP**, com fallback na **BrasilAPI**. CEP inválido ou inexistente só gera aviso — o cadastro, a edição e o checkout continuam enviáveis.

| | |
|---|---|
| Módulo | `modules/addons/buscacep` |
| Versão | 1.0.0 |
| Requisitos | WHMCS 9.x, PHP 8.2+ |
| Licença | [GPL-2.0](LICENSE) |

## O que o addon faz

- Preenche logradouro, bairro, cidade e UF a partir do CEP
- Funciona no checkout, cadastro, perfil, contatos e no admin (cliente e contato)
- Só atua quando o país é Brasil (`BR`) ou ainda está vazio
- Consulta no servidor (CSRF, cache, rate limit e log), não direto no navegador
- Máscara visual `00000-000`

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| [docs/instalacao.md](docs/instalacao.md) | Instalação, ativação, configuração e desinstalação |
| [docs/arquitetura.md](docs/arquitetura.md) | Fluxo, arquivos, APIs e segurança |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de versões |

O README operacional que viaja junto com a pasta do módulo está em [`modules/addons/buscacep/README.md`](modules/addons/buscacep/README.md).

## Instalação rápida

1. Copie `modules/addons/buscacep` para o mesmo caminho na instalação WHMCS.
2. No admin: **System Settings → Addon Modules → Activate** em **SeixasTec BuscaCEP**.
3. Marque o grupo de administradores com acesso e salve.
4. Teste um CEP em **Addons → SeixasTec BuscaCEP** (ex.: `01001-000`).

Detalhes: [docs/instalacao.md](docs/instalacao.md).

## Estrutura do repositório

```
modules/addons/buscacep/     # addon WHMCS (copiar esta pasta)
docs/                        # documentação do projeto
LICENSE
CHANGELOG.md
```

## Mapeamento de campos

| API | Campo WHMCS |
|-----|-------------|
| logradouro / street | `address1` |
| bairro / neighborhood | `address2` |
| localidade / city | `city` |
| uf / state | `state` |
| cep | `postcode` |

O número do imóvel não vem da API. Se o logradouro já estiver preenchido com o mesmo nome + número, o número é preservado.

## Licença

GNU General Public License v2.0. Veja [LICENSE](LICENSE).
