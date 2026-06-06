# TodoSync — Gestão de Tarefas Multi-dispositivo (Projeto 05)

Aplicação mobile de gestão de tarefas com sincronização em tempo real entre dispositivos, desenvolvida em Laravel + NativePHP Mobile durante o estágio na Primariu (BRATIONAL - Sistemas de Informação Lda.).

🎥 **Demonstração em vídeo:** https://www.youtube.com/watch?v=Xm9RZNKU8HM&list=PLaQbpcoT6whT2s8RyUulqr0guhVo0D0n1&index=6

> **Nota:** O servidor online (Railway) encontra-se atualmente suspenso por expiração do plano de créditos. A aplicação funcionou em produção completa conforme documentado na demonstração em vídeo.

---

## Sobre o Projeto

O **TodoSync** é a evolução do projeto Mini To-Do List, resolvendo a sua principal limitação: os dados ficavam apenas no dispositivo local. Esta versão permite que o utilizador aceda sempre às mesmas tarefas em qualquer dispositivo, com sincronização automática via servidor.

O projeto serviu também como avaliação técnica por parte da empresa antes de avançar para o projeto principal (TourOS Alpha).

---

## Funcionalidades

### Autenticação
- Registo com nome, email e password
- Login / Logout com invalidação completa de sessão
- Proteção de rotas — utilizadores não autenticados são redirecionados
- Sessões persistentes de 7 dias

### Gestão de Tarefas
- Criar tarefa com título e descrição opcional
- Editar título, descrição e configurações de repetição
- Eliminar tarefa (com confirmação)
- Marcar/desmarcar como concluída
- Lista separada entre tarefas próprias e tarefas partilhadas

### Tarefas Repetidas
- Ativação de repetição com seleção dos dias da semana (Segunda a Domingo)
- Histórico visual dos últimos 7 dias (círculo preenchido = feito, contorno = hoje, cinzento = não feito)
- Contador semanal (ex: "4/7")
- Estado independente por utilizador

### Partilha de Tarefas
- Partilhar qualquer tarefa com outro utilizador pelo email
- O utilizador convidado pode ver e marcar como concluída
- O dono pode remover partilhas
- Tarefas partilhadas aparecem numa secção separada

### Ecrã de Detalhe
- Estatísticas: feito esta semana, este mês e total
- Gráfico dos últimos 7 dias com datas
- Histórico completo agrupado por mês
- Lista de utilizadores com acesso

---

## Stack Tecnológica

| Componente | Tecnologia |
|---|---|
| Framework | Laravel + NativePHP Mobile |
| Backend | PHP 8.3 |
| Frontend | Blade + Livewire + Tailwind CSS |
| Base de Dados | MySQL (Railway) |
| Autenticação | Laravel Sanctum (sessões) |
| Plataforma | Web + Android (APK via NativePHP Mobile) |

---

## Arquitetura

A aplicação segue uma arquitetura de duas camadas:

```
┌──────────────────────────────────┐
│  App NativePHP (Android/Browser) │
│  Laravel + Livewire + Blade      │
└──────────────┬───────────────────┘
               │ HTTP (HTTPS)
               ▼
┌──────────────────────────────────┐
│  Base de Dados MySQL             │
│  Servidor Railway                │
└──────────────────────────────────┘
```

> A ligação direta ao MySQL funciona em browser. Em Android, o driver `pdo_mysql` não está disponível no PHP embutido pelo NativePHP — ver secção de Limitações.

---

## Instalação e Configuração

### Pré-requisitos
- PHP 8.3+
- Composer
- Node.js + NPM
- MySQL (local ou remoto)

### Passos

```bash
# 1. Clonar o repositório
git clone https://github.com/diogooaabreu/TodoSycn_EstagioPrimariu_26.git
cd TodoSycn_EstagioPrimariu_26

# 2. Instalar dependências PHP
composer install

# 3. Instalar dependências frontend
npm install && npm run build

# 4. Configurar o ambiente
cp .env.example .env
php artisan key:generate

# 5. Configurar a base de dados no .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=todosync
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Executar as migrações
php artisan migrate

# 7. Iniciar o servidor
php artisan serve
```

Aceder a: `http://localhost:8000`

---

## Limitações Conhecidas

**Driver `pdo_mysql` ausente no Android**

O NativePHP Mobile empacota o PHP dentro do APK Android, mas esse PHP não inclui a extensão `pdo_mysql`. O PHP embutido no Android suporta apenas SQLite.

**Solução aplicada:** A aplicação funciona via browser no telemóvel, acedendo ao servidor remoto. O NativePHP Mobile empacota a interface, mas a ligação à base de dados é feita através do servidor online.

**Solução definitiva (para projetos futuros):** Implementar uma API REST no servidor (ver [Projeto 05 — API REST](https://github.com/diogooaabreu/ESTAGIOP05_API_TodoSycn.git)).

---

## Contexto de Desenvolvimento

Este projeto foi desenvolvido como parte do estágio curricular da Licenciatura em Engenharia de Sistemas Informáticos (LESI) no IPCA, realizado na empresa Primariu. Serviu como avaliação técnica antes do desenvolvimento do TourOS Alpha e como exploração da stack NativePHP Mobile em ambiente de produção real.

---

## Licença

Projeto académico — Estágio Curricular LESI, IPCA 2025/2026.
