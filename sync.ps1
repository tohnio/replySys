<#
.SYNOPSIS
Script para sincronizar o projeto com o servidor de produção via SSH.

.DESCRIPTION
Este script empacota os arquivos locais ignorando pastas pesadas (vendor, node_modules),
envia para o servidor via SCP e extrai remotamente via SSH.
#>

$ServerUser = "root"
$ServerIP = "191.252.101.239"
$RemoteDir = "/home/mc/www/replySys"
$TarFile = "deploy.tar.gz"

Write-Host "Iniciando processo de deploy para $ServerUser@$ServerIP..." -ForegroundColor Cyan

# 1. Empacotar o projeto (ignorar pastas que devem ser recriadas no servidor)
Write-Host "Empacotando os arquivos do projeto..." -ForegroundColor Yellow
# O tar.exe é nativo no Windows 10+
tar.exe -czf $TarFile `
    --exclude="vendor" `
    --exclude="node_modules" `
    --exclude=".git" `
    --exclude="storage/framework/cache/*" `
    --exclude="storage/framework/sessions/*" `
    --exclude="storage/framework/views/*" `
    --exclude="storage/logs/*" `
    --exclude=".env" `
    --exclude=$TarFile `
    .

if (-Not (Test-Path $TarFile)) {
    Write-Host "Erro ao criar o arquivo $TarFile." -ForegroundColor Red
    exit 1
}

# 2. Enviar para o servidor usando scp (nativo no Windows)
Write-Host "Enviando arquivo para o servidor via SCP..." -ForegroundColor Yellow
# Garante que a pasta existe no servidor
ssh "$ServerUser@$ServerIP" "mkdir -p $RemoteDir"
scp $TarFile "$ServerUser@$ServerIP`:$RemoteDir/"

if ($LASTEXITCODE -ne 0) {
    Write-Host "Falha na transferência do arquivo." -ForegroundColor Red
    Remove-Item $TarFile
    exit 1
}

# 3. Extrair os arquivos no servidor, limpar e ajustar permissões
Write-Host "Extraindo arquivos no servidor e limpando..." -ForegroundColor Yellow
$SshCommand = @"
cd $RemoteDir && \
tar -xzf $TarFile && \
rm $TarFile && \
chown -R www-data:www-data . && \
chmod -R 775 storage bootstrap/cache
"@

ssh "$ServerUser@$ServerIP" $SshCommand

# 4. Limpar arquivo local
Write-Host "Limpando arquivos temporários locais..." -ForegroundColor Yellow
Remove-Item $TarFile

Write-Host "Deploy finalizado com sucesso! 🎉" -ForegroundColor Green

<# 
# ====================================================================
# OPÇÃO ALTERNATIVA COM RSYNC (Se você usa Git Bash ou WSL)
# Descomente abaixo se preferir usar rsync (muito mais rápido para atualizações diárias)
# ====================================================================
#
# rsync -avz --no-perms --no-owner --no-group --delete `
#     --exclude='/vendor' `
#     --exclude='/node_modules' `
#     --exclude='/.git' `
#     --exclude='/storage/logs/*' `
#     --exclude='/storage/framework/views/*' `
#     --exclude='/storage/framework/sessions/*' `
#     --exclude='/.env' `
#     ./ root@191.252.101.239:/home/mc/www/replySys/
#>
