# Sistema de Controle de Reparos (ReplySys)

O **ReplySys** é um ecossistema integrado para gerenciamento de ordens de serviço (OS), controle financeiro de fluxo de caixa (entradas e despesas) e automação inteligente de ligações telefônicas para notificação de clientes sobre o status de seus aparelhos.

O projeto é composto por:
1. **Backend & Painel Administrativo Web:** Desenvolvido em Laravel 11.
2. **Aplicativo Mobile:** Desenvolvido em Android (Kotlin + Jetpack Compose) para os técnicos.
3. **Automação Externa:** Fluxo no **n8n** integrado com torpedo de voz da **NVoIP** para ligações automatizadas.

---

## 🛠️ Tecnologias e Arquitetura

### Backend & Painel Web (Laravel)
- **Framework:** Laravel 11.x
- **Banco de Dados:** MySQL/PostgreSQL (Relacional)
- **Fila de Processamento:** Laravel Queues (utilizando driver de banco de dados por padrão) para processamento assíncrono de notificações de clientes.
- **Documentação de API:** L5-Swagger (OpenAPI) configurado e acessível no endpoint `/api/documentation`.
- **Automação:** Disparo de webhooks direcionados para o n8n no status `REPARADO`.

### Mobile (Android App - `replyApp`)
- **Linguagem:** Kotlin
- **Interface Gráfica:** Jetpack Compose (Modern UI reativa e fluida)
- **Integração HTTP:** Retrofit + Gson para consumo da API Laravel do backend.
- **Arquitetura:** MVVM (ViewModel, LiveData/StateFlow, Coroutines).

### Integração n8n & NVoIP
- Ao concluir um reparo (status `REPARADO`), o Laravel agenda um job assíncrono que aciona um webhook no **n8n**.
- O **n8n** atua como o orquestrador inteligente, validando as informações e realizando a chamada de torpedo de voz (TTS) através da API da **NVoIP**.
- A resposta da chamada é enviada pelo n8n de volta ao webhook do Laravel (`POST /api/webhook/n8n`), que registra o histórico, duração, transcrição e agenda retentativas inteligentes (limite de 5 tentativas) caso caia em caixa postal.

---

## 📦 Estrutura do Repositório

```
├── app/                  # Código fonte do Laravel (Models, Controllers, Services, Jobs)
├── config/               # Arquivos de configuração do Laravel
├── database/             # Migrações e seeders de banco de dados
├── docker/               # Configuração do ambiente em containers Docker (Nginx, PHP)
├── public/               # Assets públicos do Laravel
├── replyApp/             # Código-fonte do aplicativo Android (Kotlin)
├── resources/            # Views (Blade templates) e assets front-end
├── routes/               # Definição de rotas web e da API
├── tests/                # Testes automatizados (Feature & Unit)
├── dp-replysys.sh        # Script automatizado de deploy via rsync + SSH
└── gemini.md             # Guia de desenvolvimento do projeto
```

---

## 🚀 Como Executar o Projeto Localmente

### Requisitos Mínimos
- PHP 8.2 ou superior
- Composer
- MySQL 8.x ou PostgreSQL
- Node.js & NPM
- Docker & Docker Compose (opcional)

### Passo a Passo

1. **Clonar o repositório** e entrar na pasta do projeto:
   ```bash
   git clone <URL_DO_REPOSITORIO>
   cd replySys
   ```

2. **Instalar as dependências do PHP:**
   ```bash
   composer install
   ```

3. **Instalar as dependências de Front-end:**
   ```bash
   npm install && npm run build
   ```

4. **Configurar o Ambiente (.env):**
   Copie o arquivo de exemplo e defina suas chaves de banco de dados e credenciais:
   ```bash
   cp .env.example .env
   ```
   Adicione a URL do webhook do seu fluxo no n8n:
   ```env
   N8N_WEBHOOK_URL=https://n8n.seudominio.com/webhook/identificador-do-fluxo
   ```

5. **Gerar a chave do Laravel:**
   ```bash
   php artisan key:generate
   ```

6. **Executar as Migrações do Banco:**
   ```bash
   php artisan migrate
   ```

7. **Iniciar o Servidor Local:**
   ```bash
   php artisan serve
   ```
   O painel web estará disponível em `http://localhost:8000`.

8. **Executar o Processador de Filas (Queue Worker):**
   Como as ligações são processadas em segundo plano, inicie o queue worker:
   ```bash
   php artisan queue:work
   ```

---

## 🐋 Rodando com Docker

O projeto possui um ambiente pré-configurado com Docker Compose localizado no diretório `/docker` que inicia o Laravel, MySQL, Redis, phpMyAdmin e uma instância local do **n8n**.

1. Acesse a pasta docker:
   ```bash
   cd docker
   ```
2. Configure o arquivo `.env` interno na pasta `/docker` (definindo a porta `N8N_PORT=9082`).
3. Inicie os containers:
   ```bash
   docker compose up -d
   ```

- **n8n UI:** disponível em `http://localhost:9082`.
- **phpMyAdmin:** disponível em `http://localhost:9081`.
- **Laravel Web Application:** disponível em `http://localhost:9080`.

---

## 🧪 Executando os Testes Automatizados

O sistema conta com um conjunto completo de testes de integração cobrindo fluxos de ordens de serviço, fluxo de caixa e o processamento de ligações via webhook do n8n.

Para rodar os testes:
```bash
php artisan test
```

---

## 🚢 Deploy Automatizado

O script [dp-replysys.sh](file:///d:/Docker/lab/replySys/dp-replysys.sh) automatiza o deploy rsync e a execução de comandos remotos via SSH.

```bash
# Permissão de execução no script
chmod +x dp-replysys.sh

# Execução do deploy
./dp-replysys.sh
```
O script se encarrega de:
- Sincronizar os arquivos excluindo pastas de dependências locais (`vendor`, `node_modules`).
- Executar migrações do banco remotamente.
- Recriar caches de rotas e configurações.
- Reiniciar o serviço de fila no servidor remoto.
