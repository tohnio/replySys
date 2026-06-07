import os
import sys
import re
import socket
import subprocess
import json

# Força codificação UTF-8 para evitar problemas de caractere no Windows CMD/PowerShell
if sys.stdout.encoding != 'utf-8':
    try:
        sys.stdout.reconfigure(encoding='utf-8')
    except Exception:
        pass

# Cores para o terminal
GREEN = "\033[92m"
YELLOW = "\033[93m"
RED = "\033[91m"
BLUE = "\033[94m"
CYAN = "\033[96m"
END = "\033[0m"

def print_section(title):
    print(f"\n{BLUE}=== {title} ==={END}")

def print_ok(msg):
    print(f"{GREEN}[OK] {msg}{END}")

def print_warn(msg):
    print(f"{YELLOW}[AVISO] {msg}{END}")

def print_err(msg):
    print(f"{RED}[ERRO] {msg}{END}")

def print_info(msg):
    print(f"{CYAN}[INFO] {msg}{END}")

def parse_env(path):
    env = {}
    if not os.path.exists(path):
        return env
    with open(path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if '=' in line:
                key, val = line.split('=', 1)
                env[key.strip()] = val.strip().strip('"').strip("'")
    return env

def get_livekit_yaml_keys(path):
    keys = {}
    if not os.path.exists(path):
        return keys
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Tenta extrair a seção keys:
    match = re.search(r'keys:\s*\n((?:\s+[\w\-]+:\s*["\']?[^\n"\']+["\']?\s*\n?)+)', content)
    if match:
        for line in match.group(1).strip().split('\n'):
            line = line.strip()
            if ':' in line:
                k, v = line.split(':', 1)
                keys[k.strip()] = v.strip().strip('"').strip("'")
    return keys

def get_sip_yaml_keys(path):
    keys = {}
    if not os.path.exists(path):
        return keys
    with open(path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if ':' in line:
                k, v = line.split(':', 1)
                keys[k.strip()] = v.strip().strip('"').strip("'")
    return keys

def check_port_listening(port, proto='tcp'):
    if proto == 'tcp':
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(1.0)
        try:
            s.connect(('127.0.0.1', port))
            s.close()
            return True
        except Exception:
            return False
    else:
        # Para UDP é mais complexo testar de forma simples sem enviar pacotes válidos
        return None

def run_cmd(cmd):
    try:
        res = subprocess.run(cmd, shell=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=5)
        return res.returncode, res.stdout.strip(), res.stderr.strip()
    except Exception as e:
        return -1, "", str(e)

def main():
    print(f"{CYAN}========================================================={END}")
    print(f"{CYAN}   DIAGNOSTICO DO ECOSSISTEMA LIVEKIT / SIP (REPLYSYS)   {END}")
    print(f"{CYAN}========================================================={END}")

    # 1. Verificação de Arquivos e Chaves
    print_section("1. Verificacao de Arquivos de Configuracao e Chaves")
    
    root_dir = os.path.abspath(os.path.dirname(__file__))
    env_path = os.path.join(root_dir, '.env')
    lk_yaml_path = os.path.join(root_dir, 'docker', 'livekit.yaml')
    sip_yaml_path = os.path.join(root_dir, 'docker', 'livekit-sip.yaml')

    env_vars = parse_env(env_path)
    lk_keys = get_livekit_yaml_keys(lk_yaml_path)
    sip_keys = get_sip_yaml_keys(sip_yaml_path)

    env_key = env_vars.get('LIVEKIT_API_KEY')
    env_secret = env_vars.get('LIVEKIT_API_SECRET')
    
    if not env_key or not env_secret:
        print_err("LIVEKIT_API_KEY ou LIVEKIT_API_SECRET nao encontrados no .env principal.")
    else:
        print_ok(f"Chaves no .env: Key='{env_key}', Secret='***{env_secret[-3:] if len(env_secret) > 3 else ''}'")

    # Verifica livekit.yaml
    if not os.path.exists(lk_yaml_path):
        print_err(f"Arquivo de configuracao {lk_yaml_path} nao encontrado!")
    else:
        if env_key in lk_keys:
            expected_secret = lk_keys[env_key]
            if expected_secret == env_secret:
                print_ok("Chaves do .env coincidem com as configuradas em docker/livekit.yaml.")
            else:
                print_err(f"MENSAGEM DE ERRO: O segredo para a chave '{env_key}' em docker/livekit.yaml nao coincide com o do .env!")
        else:
            print_err(f"A chave '{env_key}' definida no .env NAO EXISTE dentro do bloco 'keys' em docker/livekit.yaml!")
            print_info(f"Chaves encontradas no livekit.yaml: {list(lk_keys.keys())}")

    # Verifica livekit-sip.yaml
    if not os.path.exists(sip_yaml_path):
        print_err(f"Arquivo de configuracao {sip_yaml_path} nao encontrado!")
    else:
        sip_key = sip_keys.get('api_key')
        sip_secret = sip_keys.get('api_secret')
        if sip_key == env_key and sip_secret == env_secret:
            print_ok("Chaves do .env coincidem com as configuradas em docker/livekit-sip.yaml.")
        else:
            print_err(f"MENSAGEM DE ERRO: Credenciais de API do livekit-sip.yaml nao coincidem com o .env!")
            print_info(f"livekit-sip.yaml -> api_key='{sip_key}', api_secret='***{sip_secret[-3:] if sip_secret and len(sip_secret) > 3 else ''}'")

    # 2. Verificação de Containers Docker
    print_section("2. Status dos Containers Docker")
    
    code, stdout, stderr = run_cmd("docker ps -a --format \"{{.Names}}\t{{.Status}}\"")
    if code != 0:
        print_err(f"Nao foi possivel rodar o comando docker: {stderr}")
        print_info("Certifique-se de que o Docker esta rodando e voce tem permissao.")
    else:
        containers_to_check = {
            'replysys_livekit': 'LiveKit Server',
            'replysys_livekit_sip': 'LiveKit SIP Trunking Service',
            'replysys_agent': 'Agente IA (Gemini Live)',
            'replysys_queue': 'Laravel Queue Worker',
            'replysys_app': 'Laravel App'
        }
        
        running_names = []
        for line in stdout.split('\n'):
            if not line:
                continue
            parts = line.split('\t')
            name = parts[0]
            status = parts[1]
            running_names.append(name)
            
            if name in containers_to_check:
                desc = containers_to_check[name]
                if "Up" in status:
                    print_ok(f"Container '{name}' ({desc}) esta executando: {status}")
                else:
                    print_err(f"Container '{name}' ({desc}) NAO ESTA RODANDO! Status: {status}")
                    
        for name, desc in containers_to_check.items():
            if name not in running_names:
                print_err(f"Container '{name}' ({desc}) nao foi encontrado na lista de containers!")

    # 3. Inspeção de Logs (Crucial para diagnosticar erros silenciosos)
    print_section("3. Ultimos logs dos Containers de Servicos de Voz")
    for name in ['replysys_livekit', 'replysys_livekit_sip', 'replysys_agent']:
        code, log_out, log_err = run_cmd(f"docker logs --tail 15 {name}")
        print(f"{CYAN}--- Ultimos 15 logs do container {name} ---{END}")
        if code == 0:
            print(log_out or log_err or "(Sem logs recentes)")
        else:
            print_warn(f"Nao foi possivel obter logs para {name}: {log_err}")
        print(f"{CYAN}--------------------------------------{END}")

    # 4. Verificação de Portas de Rede Local
    print_section("4. Verificacao de Portas de Rede (Localmente)")
    
    # Porta do LiveKit HTTP/WS
    if check_port_listening(7880, 'tcp'):
        print_ok("Porta 7880 (LiveKit Server HTTP/WS) esta escutando na interface local.")
    else:
        print_err("Porta 7880 (LiveKit Server) NAO esta escutando. Verifique se o container esta de pe.")

    # Porta do SIP TCP
    if check_port_listening(5060, 'tcp'):
        print_ok("Porta 5060 (SIP TCP) esta escutando na interface local.")
    else:
        print_warn("Porta 5060 (SIP TCP) nao esta escutando. Geralmente o SIP utiliza UDP, mas verifique o container livekit_sip.")

    # Tenta descobrir o processo que está usando as portas usando o netstat/ss
    code, stdout, stderr = run_cmd("ss -tulpn | grep -E '7880|5060'")
    if code == 0 and stdout:
        print_info("Processos locais ouvindo nas portas:\n" + stdout)
    else:
        code, stdout, stderr = run_cmd("netstat -tulpn | grep -E '7880|5060'")
        if code == 0 and stdout:
            print_info("Processos locais ouvindo nas portas:\n" + stdout)

    # 5. Verificação de Firewall (Linux)
    print_section("5. Verificacao do Firewall do Sistema Operacional")
    
    if os.name != 'nt': # Apenas Linux
        # Verifica ufw
        code, stdout, stderr = run_cmd("ufw status")
        if code == 0:
            print_info(f"Status do UFW (Firewall Local):\n{stdout}")
            # Verifica se 5060 e 10000-10050 estão abertos
            if "active" in stdout.lower():
                if "5060" not in stdout:
                    print_err("A porta 5060 (SIP) NAO parece estar liberada no UFW!")
                else:
                    print_ok("Porta 5060 encontrada nas regras do UFW.")
                
                if "10000:10050" not in stdout and "10000-10050" not in stdout:
                    print_err("A faixa de portas RTP (10000-10050 UDP) NAO parece estar liberada no UFW! Sem isso a chamada nao tera audio.")
                else:
                    print_ok("Faixa de portas RTP (10000-10050 UDP) configurada no UFW.")
        else:
            # Tenta iptables
            code, stdout, stderr = run_cmd("iptables -L -n | grep -E '5060|10000'")
            if code == 0 and stdout:
                print_info(f"Regras de iptables para as portas:\n{stdout}")
            else:
                print_warn("Nao foi possivel detectar o UFW ou regras especificas no iptables (talvez necessite de sudo).")
    else:
        print_info("Sistema operacional Windows detectado. Pulando checagem de firewall Linux.")

    # 6. Pistas e Dicas de Diagnóstico de Rede SIP
    print_section("6. Pistas e Orientacoes para Resolucao de Problemas")

    print(f"""{YELLOW}[!] PISTA 1: A porta 5060 NAO eh um servidor HTTP/Web.{END}
   * O protocolo SIP (porta 5060) eh um protocolo binario/texto proprio (RFC 3261).
   * Acessar 'http://191.252.101.239:5060' no navegador eh esperado falhar ou retornar 'Conexao Recusada/Reiniciada' porque a porta nao fala o protocolo HTTP.
   * A validacao de que a porta esta respondendo deve ser feita via ferramentas de teste de SIP (como `sipsak`), ou verificando se o SIP responde via UDP.

{YELLOW}[!] PISTA 2: Liberacao de Firewall Externo (Cloud / Provedor).{END}
   * Mesmo que as portas estejam abertas localmente e no UFW do Linux, se voce estiver usando um provedor de nuvem (AWS, Oracle Cloud, DigitalOcean, Azure),
     voce **precisa** liberar as portas nas regras de rede externas (Security Groups / Network Firewalls) do console do provedor:
     - {CYAN}Porta 5060 (UDP & TCP){END} - Sinalizacao SIP
     - {CYAN}Porta 7880 (TCP){END} - Conexao API LiveKit
     - {CYAN}Portas 10000 a 10050 (UDP){END} - Fluxo de Audio (RTP) - Crucial para que o audio funcione.

{YELLOW}[!] PISTA 3: Credenciais da NVoIP no .env de producao.{END}
   * Verifique se as credenciais da NVoIP no seu `.env` de producao estao 100% corretas.
   * `NVOIP_SIP_USERNAME` deve ser o ramal (geralmente composto por numeros).
   * `NVOIP_SIP_PASSWORD` deve ser a senha do ramal.
   * `NVOIP_SIP_CALLER_ID` deve ser exatamente o mesmo ramal (ou numero associado), pois se houver divergência, a NVoIP rejeitara a chamada com '403 Forbidden' ou '401 Unauthorized'.

{YELLOW}[!] PISTA 4: Testar disparo direto pelo container Laravel.{END}
   * Voce pode forcar um teste de ligacao direta rodando o script de teste dentro do container da fila ou app do Laravel para ler os logs de erro em tempo real:
     {CYAN}docker compose exec -it app python3 make_call.py --to 51985408560 --name "Teste Producao" --desc "Aparelho" --value "150.00" --external-id "teste-prod-123"{END}
   * Isso imprimira logs detalhados e mostrara qualquer erro de autenticacao ou de rede imediatamente.
""")

if __name__ == "__main__":
    main()
