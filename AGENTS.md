## Escopo
- Modulo de pessoas e empresas.
- Cobre `People`, documentos, emails, dominios, empresas, pacotes e fluxo base de criacao de conta.

## Quando usar
- Prompts sobre pessoa, empresa, documentos, contatos, dominio, company context e vinculos principais de cadastro.

## Regras de vinculo
- `people_link` e o catalogo oficial de relacionamento entre pessoa e empresa.
- Roles humanas explicitas do modulo: `employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`, `courier`.
- Roles comerciais explicitas do modulo: `client`, `provider`, `franchisee`.
- `family` e `sellers-client` nao entram como role humana de API.
- `courier` e o vinculo humano usado para entregadores da loja; nao reutilizar `carrier` como sinonimo de cadastro.
- `ROLE_SUPER` so existe quando o vinculo direto com a empresa principal for `owner`.

## Regras de acesso
- A pessoa fisica so tem permissao operacional na empresa do vinculo direto.
- Subidas de nivel na arvore de empresas servem apenas para validar a cadeia comercial ate a principal.
- `client` nao concede permissao operacional humana; ele apenas habilita o acesso comercial da empresa ao painel.
- `permission` retornado por company context deve refletir o link direto da pessoa com a empresa e a cadeia comercial valida.
- O filtro principal de dados do modulo mora em `PeopleService::securityFilter()` e helpers derivados.
- `People` nao pode expor por serializacao aninhada dados sensiveis de `User` para leitores mais amplos do que a politica direta de `User`.
- Campos como `username`, `apiKey`, hash de recuperacao, credenciais ou identificadores equivalentes de `User` nao podem sair em grupos amplos como `people:read`.
- Se `People` mantiver relacao com `User`, essa relacao so pode aparecer em grupo e operacao com autorizacao tao forte quanto a leitura direta de `User`.
- Operacoes amplas ou publicas de `People`, incluindo `Get` com `PUBLIC_ACCESS`, nunca podem se tornar caminho lateral para leitura de credenciais ou segredos de `User`.
- Listagens de `People` consumidas por `DefaultTable` React precisam de `CustomOrFilter`, `OrderFilter` e `DateFilter` alinhados com os campos declarados no store, com datas ordenando pelo valor persistido.

## Regras de vendedores e comissao
- O vinculo `sellers-client` em `people_link` e sensivel porque revela e altera a relacao comercial entre cliente e vendedor.
- Fora do contexto `APP_TYPE=MANAGER`, o ecossistema pode identificar quem e o vendedor vinculado ao cliente, mas nao pode expor `comission` nem `minimum_comission`.
- Operacoes de adicionar, editar, remover ou trocar vendedores vinculados ao cliente so podem ser liberadas no contexto `MANAGER`.
- O backend nao pode confiar apenas em ocultacao de campos no front para proteger `people_link`.
- Toda leitura e toda escrita de `people_link`, especialmente para `linkType=sellers-client`, precisa de `securityFilter` proprio no service equivalente da entidade ou protecao explicita de mesmo efeito cobrindo leitura e gravacao.
- `ROLE_HUMAN` isolado nao e protecao suficiente para esse recurso.

## Responsabilidades
- `people` e dono dos dados cadastrais e dos relacionamentos pessoa/empresa.
- `PeopleRoleService` resolve roles e empresas acessiveis a partir de `people_link`.
- `PeopleService` aplica o recorte de dados por empresa em `securityFilter`.

## Regra de extra_data
- `extra_data` e `extra_fields` nao podem guardar snapshot rico de pessoa, empresa, integracao, loja ou configuracao quando o dado ja tiver destino canonico em `People`, `people_link`, `Config` ou JSON materializado da entidade.
- Nesta area, `extra_data` so pode carregar IDs, codigos remotos e chaves de vinculo que ainda nao tenham coluna ou relacao canonica equivalente.
- Quando um dado ja estiver materializado, a persistencia nova deve ir para a entidade dona e a limpeza de legado deve remover o respectivo par `extra_data`/`extra_fields`.

## Limites
- Autenticacao, token e sessao pertencem a `users`.
