import os
import asyncio
import argparse
import logging
from dotenv import load_dotenv
from livekit import api
from livekit.api import (
    LiveKitAPI, 
    AccessToken, 
    VideoGrants, 
    CreateSIPOutboundTrunkRequest, 
    CreateSIPParticipantRequest, 
    SIPOutboundTrunkInfo,
    ListSIPOutboundTrunkRequest,
    DeleteSIPTrunkRequest
)

# Configuração de Logs
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("livekit-call-trigger")

# Carrega o .env
load_dotenv()

async def list_and_get_outbound_trunk(lkapi: LiveKitAPI, sip_address: str) -> str:
    """Verifica e remove o tronco SIP existente para garantir que as credenciais do .env sejam aplicadas."""
    logger.info("Verificando se há troncos SIP outbound antigos...")
    try:
        response = await lkapi.sip.list_outbound_trunk(ListSIPOutboundTrunkRequest())
        for trunk in response.items:
            if trunk.address == sip_address or trunk.name == "NVoIP Outbound Trunk":
                logger.info(f"Removendo tronco SIP antigo para aplicar credenciais novas: ID={trunk.sip_trunk_id}")
                await lkapi.sip.delete_trunk(DeleteSIPTrunkRequest(sip_trunk_id=trunk.sip_trunk_id))
    except Exception as e:
        logger.warning(f"Não foi possível limpar troncos SIP antigos: {e}")

    # Cria o tronco se não foi encontrado
    logger.info("Criando novo tronco SIP Outbound...")
    sip_username = os.getenv("NVOIP_SIP_USERNAME")
    sip_password = os.getenv("NVOIP_SIP_PASSWORD")
    caller_id = os.getenv("NVOIP_SIP_CALLER_ID", "555133765535")

    if not sip_username or not sip_password:
        raise ValueError("Variáveis de ambiente NVOIP_SIP_USERNAME ou NVOIP_SIP_PASSWORD não configuradas no .env!")

    trunk_info = SIPOutboundTrunkInfo(
        name="NVoIP Outbound Trunk",
        address=sip_address,
        auth_username=sip_username,
        auth_password=sip_password,
        numbers=[caller_id]
    )

    request = CreateSIPOutboundTrunkRequest(trunk=trunk_info)
    new_trunk = await lkapi.sip.create_outbound_trunk(request)
    logger.info(f"Tronco SIP Outbound criado com sucesso! ID={new_trunk.sip_trunk_id}")
    return new_trunk.sip_trunk_id

async def make_sip_call(to_number: str, room_name: str, client_name: str, description: str, value: str, external_call_id: str):
    """Inicia a ligação telefônica real enviando o participante SIP para a sala com atributos."""
    url = os.getenv("LIVEKIT_URL", "ws://localhost:7880")
    # Se estiver rodando dentro do container Docker e a URL apontar para localhost,
    # substitui pelo hostname do container do LiveKit
    if os.path.exists('/.dockerenv') and "localhost" in url:
        url = url.replace("localhost", "livekit")

    api_key = os.getenv("LIVEKIT_API_KEY", "devkey")
    api_secret = os.getenv("LIVEKIT_API_SECRET", "secret")
    sip_address = os.getenv("NVOIP_SIP_ADDRESS", "sip.nvoip.com.br")

    # LiveKit API cliente necessita de HTTP/HTTPS (não ws/wss)
    http_url = url.replace("ws://", "http://").replace("wss://", "https://")
    logger.info(f"Conectando ao LiveKit API Server em {http_url}...")

    lkapi = LiveKitAPI(url=http_url, api_key=api_key, api_secret=api_secret)
    try:
        # 1. Obtém ou cria o tronco SIP
        sip_trunk_id = await list_and_get_outbound_trunk(lkapi, sip_address)

        # 2. Telefona discando para o destinatário (adiciona como participante na sala)
        logger.info(f"Disparando ligação SIP Outbound para o número {to_number} na sala '{room_name}'...")
        request = CreateSIPParticipantRequest(
            sip_trunk_id=sip_trunk_id,
            sip_call_to=to_number,
            room_name=room_name,
            participant_attributes={
                "client_name": client_name,
                "order_description": description,
                "order_value": value,
                "external_call_id": external_call_id
            }
        )
        participant = await lkapi.sip.create_sip_participant(request)
        logger.info(f"Chamada iniciada com sucesso!")
        logger.info(f"Participante SIP ID: {participant.participant_id}")
        logger.info(f"Identidade do participante SIP: {participant.participant_identity}")
    finally:
        await lkapi.aclose()

def generate_simulation_link(room_name: str):
    """Gera um token de acesso e um link de Sandbox do LiveKit para simulação local."""
    url = os.getenv("LIVEKIT_URL", "ws://localhost:7880")
    api_key = os.getenv("LIVEKIT_API_KEY", "devkey")
    api_secret = os.getenv("LIVEKIT_API_SECRET", "secret")

    logger.info(f"Gerando token de simulação local para a sala '{room_name}'...")
    token = AccessToken(api_key, api_secret) \
        .with_identity("simulated-customer") \
        .with_name("Cliente Simulado") \
        .with_grants(VideoGrants(room_join=True, room=room_name)) \
        .to_jwt()

    sandbox_url = f"https://sandbox.livekit.io/?url={url}&token={token}"
    
    print("\n" + "="*80)
    print(" MODO SIMULAÇÃO DE VOZ ATIVADO (GEMINI LIVE)")
    print("="*80)
    print(f"1. Inicie o agente de voz rodando no terminal:")
    print("   python agent.py dev")
    print(f"\n2. Copie e abra o link abaixo no seu navegador:")
    print(f"   {sandbox_url}")
    print("\n3. Fale no microfone e interaja diretamente com o Gemini Live!")
    print("="*80 + "\n")

def clean_phone_number(phone: str) -> str:
    """Limpa e formata o número de telefone removendo o '55' inicial, pois a NVoIP o adiciona automaticamente."""
    cleaned = "".join(c for c in phone if c.isdigit())
    
    # Se o número começa com 55 e tem 12 ou 13 dígitos, remove o 55
    if cleaned.startswith("55") and len(cleaned) in (12, 13):
        cleaned = cleaned[2:]
        
    return cleaned

def main():
    parser = argparse.ArgumentParser(description="LiveKit SIP Outbound Call & Gemini Live Simulation Trigger")
    parser.add_argument("--to", "-t", help="Número do telefone destinatário (ex: +5551999998888 ou 51999998888)")
    parser.add_argument("--room", "-r", default="os-calling-room", help="Nome da sala do LiveKit")
    parser.add_argument("--simulate", "-s", action="store_true", help="Gera link para simulação via navegador")
    parser.add_argument("--name", "-n", default="Cliente", help="Nome do cliente")
    parser.add_argument("--desc", "-d", default="sapato", help="Descrição do produto reparado")
    parser.add_argument("--value", "-v", default="0.00", help="Valor cobrado pelo reparo")
    parser.add_argument("--external-id", "-e", default="", help="UUID da chamada para tracking no Laravel")

    args = parser.parse_args()

    if args.simulate:
        generate_simulation_link(args.room)
    elif args.to:
        cleaned_number = clean_phone_number(args.to)
        logger.info(f"Número de telefone normalizado para NVoIP SIP: {cleaned_number}")
        asyncio.run(make_sip_call(cleaned_number, args.room, args.name, args.desc, args.value, args.external_id))
    else:
        parser.print_help()

if __name__ == "__main__":
    main()
