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
    "$SOURCE_DIR" "$DEST_USER@$DEST_IP:$DEST_DIR"

if [ $? -eq 0 ]; then
    echo "========================================================="
    echo " Sincronização de arquivos concluída com sucesso! 🎉"
    echo " Executando comandos de deploy pós-sincronização no servidor..."
    echo "========================================================="

    # Executa comandos pós-deploy via SSH remoto usando sshpass
    sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no "$DEST_USER@$DEST_IP" "
        echo '-> Limpando arquivos de cache locais temporários no servidor...' && \
        rm -f $DEST_DIR/bootstrap/cache/*.php && \
        
        cd $DEST_DIR/docker && \
        
        echo '-> Ativando modo de manutenção...' && \
        docker compose exec -T app php artisan down && \
        
        echo '-> Instalando dependências do Composer...' && \
        docker compose exec -T app composer install --no-dev --optimize-autoloader --no-interaction && \
        
        echo '-> Executando migrações do banco...' && \
        docker compose exec -T app php artisan migrate --force && \
        
        echo '-> Limpando e gerando caches...' && \
        docker compose exec -T app php artisan config:cache && \
        docker compose exec -T app php artisan route:cache && \
        docker compose exec -T app php artisan view:cache && \
        
        echo '-> Reiniciando fila de jobs (queue:work/queue:listen)...' && \
        # Caso use 'queue:work' com Supervisor ou daemon no container (Recomendado):
        docker compose exec -T app php artisan queue:restart && \
        
        # Caso use 'queue:listen' manual em background no container, descomente abaixo:
        # docker compose exec -T app pkill -f 'artisan queue:listen' || true
        # docker compose exec -d app php artisan queue:listen --queue=default
        
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