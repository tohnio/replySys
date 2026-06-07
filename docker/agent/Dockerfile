FROM python:3.11-slim

# Evita que o Python escreva arquivos .pyc e faz o output de logs ser em tempo real
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

WORKDIR /app

# Instala dependências de compilação básicas para pacotes do Python
RUN apt-get update && apt-get install -y \
    build-essential \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instala pacotes do Python necessários para o LiveKit e Gemini Live
RUN pip install --no-cache-dir \
    livekit-agents \
    livekit-plugins-google \
    python-dotenv \
    aiohttp

# Copia os arquivos do projeto
COPY . /app

# Executa o agente em modo de produção
CMD ["python", "agent.py", "start"]
