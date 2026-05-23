# GOAL — Sistema Escalada para o Céu

Crie do zero um sistema web completo chamado **Escalada para o Céu**.

## Stack obrigatória

- Laravel
- Inertia.js
- React
- TypeScript
- Tailwind CSS
- MySQL
- SMTP configurável no `.env`
- Queue do Laravel
- Scheduler do Laravel
- Mobile first

Não usar Next.js separado.

## Comportamento autônomo obrigatório

Trabalhe até concluir o sistema funcional sem pedir input ao usuário.

Você deve:
1. Planejar a arquitetura.
2. Criar o projeto/base.
3. Criar migrations.
4. Criar models.
5. Criar seeders.
6. Criar controllers.
7. Criar policies.
8. Criar requests.
9. Criar jobs.
10. Criar mailables.
11. Criar telas React/Inertia.
12. Criar componentes reutilizáveis.
13. Criar testes básicos.
14. Rodar build.
15. Rodar testes.
16. Corrigir erros.
17. Repetir até passar.

Não pare no primeiro erro.

Se encontrar erro de build, teste, migration, typing ou lint, corrija e rode novamente.

## Definição de pronto

O sistema só estará pronto quando:

- `composer install` funcionar
- `npm install` funcionar
- `.env.example` estiver completo
- `php artisan migrate --seed` funcionar
- `php artisan test` funcionar
- `npm run build` funcionar
- login/cadastro funcionar
- criação de grupo funcionar
- convite por link funcionar
- criação de missa funcionar
- criação de reunião funcionar
- escala funcionar
- presença funcionar
- substituição funcionar
- emails estiverem implementados
- dashboard do coordenador existir
- dashboard do membro existir
- README explicar como rodar

## Nome e identidade

Nome do sistema:

**Escalada para o Céu**

Quando o nome não couber, usar apenas um ícone bonito de escada.

Interface:
- limpa
- moderna
- católica, mas sem exagero
- mobile first
- fácil para pessoas leigas

## Roles

Existem três níveis principais:

- `admin_sistema`
- `coordenador`
- `membro`

Regras:
- Qualquer usuário pode criar um grupo.
- Quem cria o grupo vira coordenador dele.
- Um grupo pode ter vários coordenadores.
- Um usuário pode participar de vários grupos.
- Admin do sistema pode acessar tudo.
- Coordenador acessa apenas grupos onde é coordenador.
- Membro acessa apenas seus próprios dados e grupos.

## Grupos

Criar funcionalidade para:

- criar grupo
- editar grupo
- listar membros
- remover membros
- aprovar membros pendentes
- alterar role dentro do grupo
- gerar link de convite para membro
- gerar link de convite para coordenador

O link de convite deve ter configuração:

- entrada livre
- entrada mediante aprovação

Mesmo com entrada livre, o coordenador pode excluir pessoas do grupo.

## Cadastro via convite

Ao entrar por link:

- usuário cria conta já vinculado ao grupo certo
- se link for de membro, entra como membro
- se link for de coordenador, entra como coordenador
- se link exigir aprovação, membership fica pendente
- coordenador aprova ou rejeita

## Eventos

O sistema deve ter calendário.

Tipos de evento:

- missa
- reunião

Campos comuns:

- grupo
- tipo
- nome
- data
- horário
- local
- observações
- status
- criado_por
- atualizado_por

Eventos podem ser editados.

## Local

Por agora não integrar Google Maps.

Campos:

- nome obrigatório
- rua opcional
- número opcional
- bairro opcional
- cidade opcional
- estado opcional
- complemento opcional

## Missa

Campos específicos:

- cor litúrgica obrigatória

Cores permitidas:

- branco
- vermelho
- verde
- roxo
- rosa
- preto

## Funções da missa

Funções padrão marcadas inicialmente:

- missal
- auxiliar

Elas podem ser desmarcadas.

Regra:
- uma missa não pode ser criada sem nenhuma função marcada.

Funções disponíveis, mas inicialmente não ativas:

- turíbulo
- naveta
- mitra
- báculo
- auxiliar do bispo
- sacrofonista

O coordenador pode criar função personalizada.

Exemplo:
- cerimoniário 3

Quando uma função personalizada for criada em um grupo, ela deve aparecer como opção nas próximas missas daquele grupo.

Não permitir múltiplas pessoas na mesma função.

Se precisar de mais pessoas, o coordenador cria mais funções.

## Escala

Ao marcar uma função na missa, abrir campo para escolher pessoa.

A pessoa pode ser:

1. usuário real já cadastrado no grupo
2. pessoa sem conta

Se a pessoa não tiver conta:

- salvar nome
- criar conta fantasma
- vincular conta fantasma ao grupo
- permitir usar essa conta fantasma em escalas futuras

Quando alguém se cadastrar depois no mesmo grupo:

- comparar nome com contas fantasmas do grupo
- se houver possível correspondência, perguntar se ela é aquela pessoa
- se confirmar, vincular conta real ao histórico da conta fantasma
- manter histórico de escalas

## Emails

Usar SMTP configurável no `.env`.

Criar mailables/jobs para:

1. pessoa escalada
2. lembrete um dia antes
3. lembrete uma hora antes
4. cobrança de presença após evento
5. aviso de vaga em missa
6. resumo ao coordenador 24h após missa

Emails devem conter:

- nome do sistema
- nome do grupo
- nome do evento
- data
- horário
- local
- função
- botão para abrir o sistema

## Scheduler

Criar scheduler para:

- lembrete 1 dia antes
- lembrete 1 hora antes
- cobrança de presença após evento
- resumo 24h depois
- aviso de vagas

## Presença

Depois da missa ou reunião, ao abrir o sistema, se houver presença pendente, mostrar popup obrigatório.

Popup:

- “Você compareceu?”
- Botões:
  - “Sim, compareci”
  - “Não compareci”

Se responder não:

- perguntar se alguém foi no lugar dela
- se sim, selecionar usuário existente ou informar nome
- registrar substituição
- substituto recebe pendência de presença
- se substituto disser não, repetir fluxo

Se ninguém informar nada:

- manter como não computado

Status:

- pendente
- compareceu
- não_compareceu
- substituído
- não_computado

## Substituições

Podem ser feitas por:

- coordenador
- própria pessoa antes da missa
- própria pessoa após dizer que não compareceu

Substituto pode ser:

- usuário existente
- nome manual com conta fantasma

## Ambientes da missa

Toda missa possui dois ambientes:

1. Turíbulo
2. Altar

### Turíbulo

Se a missa tiver função `turíbulo`, deve existir também `naveta`.

Se tiver turíbulo/naveta, deve aparecer área para enviar:

- foto do turíbulo
- comentário

Apenas uma pessoa precisa enviar.

Se alguém enviar, não precisa outra pessoa enviar.

### Altar

Funções de altar são todas as outras que não são turíbulo/naveta.

Qualquer pessoa escalada em função de altar pode enviar:

- foto do altar
- observação

Apenas uma pessoa precisa enviar.

## Reuniões

Reunião tem presença.

Coordenador pode criar reunião para:

- todos do grupo
- convidados específicos

Coordenador pode preencher lista de presença manualmente.

Usuário também pode confirmar presença pelo popup.

## Missa com vagas

Deve ser possível criar missa sem pessoas escaladas, mas com funções/vagas.

Nesse caso:

- notificar membros do grupo por email
- membros podem se candidatar para servir
- coordenador aprova/escolhe quem fica em cada função

## Dashboard do coordenador

Mostrar:

- próximas missas
- próximas reuniões
- eventos com presença pendente
- membros do grupo
- solicitações pendentes
- resumo da última missa
- pessoas que não confirmaram presença
- fotos pendentes de altar/turíbulo
- vagas abertas

## Insights

Coordenador deve ver:

- presença por missa
- presença por reunião
- índice de presença por pessoa
- índice de faltas por pessoa
- escalas não computadas
- substituições
- ranking de assiduidade

Color coding:

- verde: presença alta
- amarelo: atenção
- vermelho: baixa presença
- cinza: poucos dados

## Tela do usuário

Mostrar:

- próximas missas em que vai servir
- próximas reuniões
- pendências de presença
- pendências de foto/observação
- calendário com próximas missas do grupo
- detalhes da escala

## Calendário

Criar visual:

- lista mobile
- calendário mensal se viável

Regras:

- missas coloridas pela cor litúrgica
- reuniões com cor neutra
- clicar no evento abre detalhes

## UI/UX

Obrigatório:

- mobile first
- cards
- badges
- ícones
- cores suaves
- layout limpo
- painel lateral no desktop
- navegação simples no mobile

## Segurança

Obrigatório:

- Policies/Gates
- Form Requests
- validação backend
- coordenador não pode editar grupo que não coordena
- membro não pode ver dados internos de outro grupo
- admin_sistema pode tudo

## Banco de dados

Criar migrations completas.

Usar soft deletes onde fizer sentido:

- groups
- events
- locations
- functions
- assignments
- memberships

## Seeds

Criar seeders com:

- usuário admin
- coordenador exemplo
- membros exemplo
- grupo exemplo
- funções padrão
- missa exemplo
- reunião exemplo
- escala exemplo

## Testes básicos

Criar testes para:

- criação de grupo
- convite de membro
- convite de coordenador
- criação de missa
- criação de reunião
- escala com usuário real
- escala com conta fantasma
- confirmação de presença
- substituição
- permissões coordenador/membro

## README

README deve explicar:

- requisitos
- instalação
- configuração `.env`
- SMTP
- queue
- scheduler
- comandos para rodar
- usuário seedado
- como testar

## Comandos finais obrigatórios

Ao final, rode:

```bash
php artisan migrate:fresh --seed
php artisan test
npm run build