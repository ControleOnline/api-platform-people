## Escopo
- Modulo de pessoas e empresas.
- Cobre `People`, documentos, emails, dominios, empresas, pacotes e fluxo base de criacao de conta.

## Quando usar
- Prompts sobre pessoa, empresa, documentos, contatos, dominio, company context e vinculos principais de cadastro.

## Regras de vinculo
- `people_link` e o catalogo oficial de relacionamento entre pessoa e empresa.
- Roles humanas explicitas do modulo: `employee`, `owner`, `director`, `manager`, `salesman`, `after-sales`.
- Roles comerciais explicitas do modulo: `client`, `provider`, `franchisee`.
- `family` e `sellers-client` nao entram como role humana de API.
- `ROLE_SUPER` so existe quando o vinculo direto com a empresa principal for `owner`.

## Regras de acesso
- A pessoa fisica so tem permissao operacional na empresa do vinculo direto.
- Subidas de nivel na arvore de empresas servem apenas para validar a cadeia comercial ate a principal.
- `client` nao concede permissao operacional humana; ele apenas habilita o acesso comercial da empresa ao painel.
- `permission` retornado por company context deve refletir o link direto da pessoa com a empresa e a cadeia comercial valida.
- O filtro principal de dados do modulo mora em `PeopleService::securityFilter()` e helpers derivados.

## Responsabilidades
- `people` e dono dos dados cadastrais e dos relacionamentos pessoa/empresa.
- `PeopleRoleService` resolve roles e empresas acessiveis a partir de `people_link`.
- `PeopleService` aplica o recorte de dados por empresa em `securityFilter`.

## Limites
- Autenticacao, token e sessao pertencem a `users`.
