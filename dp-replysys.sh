#copia do seriço de deploy replysys
#!/bin/bash
# Caminho de origem no formato do Cygwin para o seu projeto no disco D:
SOURCE_DIR="/cygdrive/d/Docker/lab/replySys/"

# Configurações do Servidor Remoto
DEST_USER="root"
DEST_IP="[IP_ADDRESS]"
DEST_DIR="/home/mc/www/replySys/"
PASSWORD="[PASSWORD]"

echo "========================================================="
echo " Iniciando sincronização com $DEST_USER@$DEST_IP"
echo "========================================================="

# Usando o sshpass para injetar a senha de forma automatizada no rsync
sshpass -p "$PASSWORD" rsync -avz --no-perms --no-owner --no-group --delete \
    -e "ssh -o StrictHostKeyChecking=no" \
    --exclude='/vendor' \
    --exclude='/node_modules' \
    --exclude='/.git' \
    --exclude='/storage/logs/*' \
    --exclude='/storage/framework/views/*' \
    --exclude='/storage/framework/sessions/*' \
    --exclude='/bootstrap/cache/*.php' \
    --exclude='/.env' \
    --exclude='/sync.ps1' \
    --exclude='/.venv' \
    --exclude='/venv' \
    --exclude='**/__pycache__' \
    --exclude='**/*.pyc' \
    "$SOURCE_DIR" "$DEST_USER@$DEST_IP:$DEST_DIR"

if [ $? -eq 0 ]; then
    echo "========================================================="
    echo " Sincronização de arquivos concluída com sucesso! 🎉"
    echo " Executando comandos de deploy pós-sincronização no servidor..."
    echo "========================================================="

    # Executa comandos pós-deploy via SSH remoto usando sshpass
    sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no "$DEST_USER@$DEST_IP" "
        echo '-> Verificando variáveis de ambiente críticas no .env...' && \
        if [ ! -f "$DEST_DIR/.env" ]; then
            echo "❌ ERRO CRÍTICO: .env não encontrado em $DEST_DIR!"
        else
            grep -q '^N8N_WHATSAPP_WEBHOOK_URL=' "$DEST_DIR/.env" || echo "⚠️ AVISO: N8N_WHATSAPP_WEBHOOK_URL não está configurada no .env de produção!"
            grep -q '^N8N_WEBHOOK_URL=' "$DEST_DIR/.env" || echo "⚠️ AVISO: N8N_WEBHOOK_URL não está configurada no .env de produção!"
            grep -q '^QUEUE_CONNECTION=' "$DEST_DIR/.env" || echo "⚠️ AVISO: QUEUE_CONNECTION não está configurada no .env de produção!"
            grep -q '^GOOGLE_API_KEY=' "$DEST_DIR/.env" || echo "⚠️ AVISO: GOOGLE_API_KEY não está configurada para o Gemini Live!"
            grep -q '^LIVEKIT_URL=' "$DEST_DIR/.env" || echo "⚠️ AVISO: LIVEKIT_URL não está configurada para o LiveKit!"
            grep -q '^LIVEKIT_API_KEY=' "$DEST_DIR/.env" || echo "⚠️ AVISO: LIVEKIT_API_KEY não está configurada!"
            grep -q '^LIVEKIT_API_SECRET=' "$DEST_DIR/.env" || echo "⚠️ AVISO: LIVEKIT_API_SECRET não está configurada!"
            grep -q '^NVOIP_SIP_ADDRESS=' "$DEST_DIR/.env" || echo "⚠️ AVISO: NVOIP_SIP_ADDRESS não está configurada!"
            grep -q '^NVOIP_SIP_USERNAME=' "$DEST_DIR/.env" || echo "⚠️ AVISO: NVOIP_SIP_USERNAME não está configurada!"
            grep -q '^NVOIP_SIP_PASSWORD=' "$DEST_DIR/.env" || echo "⚠️ AVISO: NVOIP_SIP_PASSWORD não está configurada!"
            grep -q '^NVOIP_SIP_CALLER_ID=' "$DEST_DIR/.env" || echo "⚠️ AVISO: NVOIP_SIP_CALLER_ID não está configurada!"
        fi && \
        
        echo '-> Limpando arquivos de cache locais temporários no servidor...' && \
        rm -f $DEST_DIR/bootstrap/cache/*.php && \
        
        cd $DEST_DIR/docker && \
        
        echo '-> Ativando modo de manutenção...' && \
        docker compose exec -T app php artisan down && \
        
        echo '-> Reconstruindo e atualizando os containers (incluindo agent de IA)...' && \
        docker compose up -d --build && \
        
        echo '-> Instalando dependências do Composer...' && \
        docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction && \
        
        echo '-> Executando migrações do banco...' && \
        docker compose exec -T app php artisan migrate --force && \
        
        echo '-> Limpando e gerando caches...' && \
        docker compose exec -T app php artisan config:cache && \
        docker compose exec -T app php artisan route:cache && \
        docker compose exec -T app php artisan view:cache && \
        
        echo '-> Reiniciando container de fila de jobs (queue) e sinalizando restart...' && \
        docker compose restart app && \
        docker compose restart queue && \
        docker compose exec -T app php artisan queue:restart && \
        
        echo '-> Desativando modo de manutenção (Online)...' && \
        docker compose exec -T app php artisan up
    "

    if [ $? -eq 0 ]; then
        echo "========================================================="
        echo " Deploy e otimizações concluídos com sucesso no servidor! 🚀"
        echo "========================================================="
    else
        echo "========================================================="
        echo " Erro ao executar comandos pós-sincronização no servidor remoto."
        echo "========================================================="
    fi
else
    echo "========================================================="
    echo " Ocorreu um erro durante a sincronização."
    echo " Certifique-se de que o pacote 'sshpass' está instalado"
    echo " no seu Cygwin (você pode instalar pelo setup do Cygwin)."
    echo "========================================================="
fi