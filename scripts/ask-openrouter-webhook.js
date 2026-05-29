#!/usr/bin/env node

const DEFAULT_TEST_URL = 'http://localhost:9082/webhook-test/replysys-openrouter-question';
const DEFAULT_PROD_URL = 'http://localhost:9082/webhook/replysys-openrouter-question';

function help() {
  console.log(`Uso:
  node scripts/ask-openrouter-webhook.js "Sua pergunta"
  node scripts/ask-openrouter-webhook.js --prod "Sua pergunta"
  node scripts/ask-openrouter-webhook.js --url https://n8n.seudominio.com/webhook/replysys-openrouter-question "Sua pergunta"

Variaveis opcionais:
  N8N_WEBHOOK_TEST_URL   URL de teste do n8n
  N8N_WEBHOOK_PROD_URL   URL de producao do n8n`);
}

const args = process.argv.slice(2);

if (args.includes('-h') || args.includes('--help')) {
  help();
  process.exit(0);
}

let mode = 'test';
let customUrl = '';
const questionParts = [];

for (let i = 0; i < args.length; i += 1) {
  const arg = args[i];

  if (arg === '--prod') {
    mode = 'prod';
    continue;
  }

  if (arg === '--test') {
    mode = 'test';
    continue;
  }

  if (arg === '--url') {
    customUrl = args[i + 1] || '';
    i += 1;
    continue;
  }

  questionParts.push(arg);
}

const question = questionParts.join(' ').trim();

if (!question) {
  console.error('Informe uma pergunta. Exemplo: node scripts/ask-openrouter-webhook.js "O webhook esta funcionando?"');
  process.exit(1);
}

const baseUrl = customUrl
  || (mode === 'prod'
    ? process.env.N8N_WEBHOOK_PROD_URL || DEFAULT_PROD_URL
    : process.env.N8N_WEBHOOK_TEST_URL || DEFAULT_TEST_URL);

const url = new URL(baseUrl);
url.searchParams.set('q', question);

const response = await fetch(url, {
  method: 'GET',
  headers: {
    Accept: 'application/json'
  }
});

const body = await response.text();

if (!response.ok) {
  console.error(`Erro HTTP ${response.status} ao chamar ${url.toString()}`);
  console.error(body);
  process.exit(1);
}

try {
  const json = JSON.parse(body);
  console.log(json.resposta || JSON.stringify(json, null, 2));
} catch {
  console.log(body);
}
