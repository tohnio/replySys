# Guia de Configuração: Fluxo Inteligente n8n + NVoIP

Este documento descreve o roteiro, o prompt da Inteligência Artificial e a estrutura dos nós no **n8n** para realizar ligações inteligentes de pós-reparo aos clientes da **ReplySys** utilizando a API da **NVoIP**.

---

## 📞 1. Objetivo da Ligação

O fluxo automatizado de voz deve:
1. Notificar o cliente de que o aparelho dele foi reparado e está pronto para retirada.
2. Informar o valor restante a ser pago (se houver).
3. Responder a dúvidas do cliente sobre o **endereço da loja** e o **horário de funcionamento**.
4. Reportar o resultado final (atendida, caixa postal, duração e notas/transcrição) de volta ao backend Laravel.

---

## 💬 2. Roteiro e Comportamento da IA (Script)

A conversa é conduzida por um agente de IA conversacional. Abaixo está o mapeamento dos diálogos esperados:

### Cenário A: Fluxo Direto (Cliente apenas confirma)
* **IA:** "Olá, [Nome do Cliente]! Aqui é a assistente virtual da ReplySys. Estou ligando para avisar que o seu [Modelo do Aparelho] já está consertado e pronto para retirada!"
* **IA:** *(Se houver saldo pendente)* "O valor restante do orçamento é de R$ [Valor Restante]. Você pode vir buscar quando quiser."
* **Cliente:** "Ah, ótimo! Muito obrigado pelo aviso. Vou passar aí hoje."
* **IA:** "Excelente! Ficamos no aguardo. Tenha um ótimo dia e até logo!"

### Cenário B: Dúvida sobre Endereço ou Horário
* **IA:** "...seu [Modelo do Aparelho] já está pronto para retirada!"
* **Cliente:** "Obrigado! Onde fica a loja mesmo? E até que horas vocês ficam abertos?"
* **IA:** "Nós ficamos na **Rua dos Reparos, nº 123, Centro - São Paulo/SP**. Nosso horário de funcionamento é de **segunda a sexta-feira, das 09h às 18h**."
* **Cliente:** "Perfeito, vou passar aí amanhã de tarde."
* **IA:** "Combinado! Estaremos te esperando. Tem mais alguma dúvida em que eu possa ajudar?"
* **Cliente:** "Não, só isso."
* **IA:** "Tenha um ótimo dia! Até mais."

---

## 🤖 3. Prompt do Sistema (System Prompt)

Configure este prompt dentro do nó do **AI Agent / LLM** no n8n para guiar a voz e as respostas da IA:

```text
Você é a assistente virtual inteligente da Sapataria Souza, uma sapataria localzada no bairro cavalhada de porto alegre.
Sua missão é ser extremamente educada, objetiva e falar em português brasileiro natural (pt-BR).

DADOS DA LIGAÇÃO:
- Cliente: {{ $json.cliente.nome }}
- Aparelho: {{ $json.item_reparado }}
- Valor a pagar: R$ {{ ($json.valor_orcamento - $json.valor_pago).toFixed(2) }}

DADOS DA LOJA (Se o cliente perguntar):
- Endereço: otto niemeyer, 3210 - cavalhada, Porto Alegre - RS.
- Horário de Funcionamento: Segunda a Sexta-feira, das 09:00 às 18:00 (Não abrimos aos sábados e domingos).

DIRETRIZES DE COMPORTAMENTO:
1. Inicie a chamada cumprimentando o cliente pelo nome e informando que o sapato dele está reparado e pronto para retirada.
2. Se o cliente perguntar sobre a localização ou horário, responda de forma clara usando estritamente os DADOS DA LOJA informados acima.
3. Mantenha as respostas curtas e naturais, adequadas para uma conversa telefônica (evite textos longos ou listas complexas).
4. Assim que o cliente confirmar que entendeu ou se despedir, finalize a chamada de forma educada e encerre a conexão.
```

---

## ⚙️ 4. Estrutura do Fluxo no n8n

> [!TIP]
> **Template Pronto para Importação:** Criamos um arquivo JSON de template completo no caminho [replysys-n8n-workflow.json](file:///d:/Docker/lab/replySys/docker/replysys-n8n-workflow.json).
> Para importar no seu n8n:
> 1. Abra e copie todo o conteúdo do arquivo [replysys-n8n-workflow.json](file:///d:/Docker/lab/replySys/docker/replysys-n8n-workflow.json).
> 2. No painel do seu n8n, crie um novo fluxo de trabalho (Workflow).
> 3. Clique em qualquer área vazia do editor/canvas e pressione **Ctrl + V** (ou Command + V no Mac).
> 4. O n8n irá importar e conectar instantaneamente todos os nós configurados!

Para implementar a integração assíncrona de torpedo de voz com o NVoIP, o fluxo no n8n é dividido em duas rotas independentes:

```mermaid
graph TD
    subgraph Fluxo A: Inicia Chamada (Disparado pelo Laravel)
        A1[1. Webhook: Recebe OS] --> A2[2. Code: Prepara Dados]
        A2 --> A3[3. NVoIP: Inicia Chamada]
        A3 --> A4[4. HTTP Request: Salva UUID no Laravel]
    end

    subgraph Fluxo B: Callback (Disparado pelo NVoIP ao finalizar)
        B1[5. Webhook: NVoIP Callback Trigger] --> B2[6. HTTP Request: Retorna Resultado]
    end
```

### Detalhamento dos Nós:

#### **Fluxo A: Inicia Chamada (Laravel -> n8n -> NVoIP)**

1. **1. Webhook: Recebe OS do Laravel:**
   * **Método:** `POST`
   * **Path:** `replysys-notification-flow`
   * Recebe as informações básicas do conserto enviadas pelo Laravel ao entrar no status `REPARADO`.

2. **2. Code: Prepara Dados:**
   * Formata os campos de valores, nome, e remove o caractere `+` do telefone para adequação à validação do NVoIP (limite de 13 dígitos).

3. **3. NVoIP: Inicia Chamada:**
   * Utiliza o nó da extensão oficial `@nvoip/n8n-nodes-nvoip` configurado com a credencial OAuth2 `nvoipAccessTokenApi`.
   * Envia o torpedo de voz (TTS) para o número do cliente com a mensagem customizada de que o item está pronto.

4. **4. HTTP Request: Salva UUID no Laravel:**
   * Dispara um callback rápido de volta ao Laravel (`callback_url`), atualizando o campo `external_call_id` da tentativa da ligação com o ID exclusivo (`uuid`) retornado pela API da NVoIP, mantendo o status em `pendente`.

#### **Fluxo B: Callback de Status (NVoIP -> n8n -> Laravel)**

5. **5. Webhook: NVoIP Callback Trigger:**
   * **Método:** `POST`
   * **Path:** `nvoip-call-ended-callback`
   * **Configuração:** Esta URL deve ser cadastrada na sua conta NVoIP (no portal do cliente) para que o NVoIP notifique o n8n assim que a chamada terminar.

6. **6. HTTP Request: Retorna Resultado ao Laravel:**
   * Recebe o payload do NVoIP contendo o `uuid`, `duration` e `status` da chamada.
   * Envia uma requisição HTTP de volta ao Laravel atualizando a tentativa com o status final (`atendida` ou `caixa_postal`), disparando a lógica de novas tentativas automáticas se necessário.
   * **URL:** `http://nginx/api/webhook/n8n`
   * **Payload:**
     ```json
     {
       "external_call_id": "{{ $json.body.uuid }}",
       "status_ligacao": "atendida", // ou caixa_postal
       "duracao": 45, // em segundos
       "transcricao_ia": "Chamada terminada. Status NVoIP: success"
     }
     ```

---

## 🔁 5. Política de Retentativas (Retry Policy)

Caso o cliente não atenda à ligação (caixa postal, desligamento ou falha):
- O sistema agenda automaticamente uma nova chamada para **1 hora de atraso**.
- As tentativas ocorrem estritamente dentro do horário comercial: das **09:00 às 18:00**. Ligações reagendadas para após as 18:00 são postergadas para as 09:00 do dia seguinte.
- O limite total é de **4 tentativas** (1 chamada inicial + 3 retentativas). Se todas falharem, o ciclo de ligações para a OS é encerrado.
