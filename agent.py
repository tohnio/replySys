import logging
import asyncio
import os
import aiohttp
from dotenv import load_dotenv
from livekit.agents import Agent, AgentSession, JobContext, WorkerOptions, cli
from livekit.plugins.google.realtime import RealtimeModel

# Configuração de Logs
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("gemini-live-agent")

# Carrega variáveis do arquivo .env
load_dotenv()

async def entrypoint(ctx: JobContext):
    logger.info("Nova chamada/conexão detectada! Conectando à sala...")
    await ctx.connect()
    logger.info(f"Conectado com sucesso à sala: {ctx.room.name}")

    # Encontra o participante SIP e extrai os atributos enviados pelo make_call
    client_name = "Cliente"
    order_description = "pedido"
    order_value = "0,00"
    external_call_id = None

    for _ in range(10):
        sip_participant = None
        for p in ctx.room.remote_participants.values():
            if p.identity.startswith("sip_"):
                sip_participant = p
                break
        if sip_participant and sip_participant.attributes:
            client_name = sip_participant.attributes.get("client_name", client_name)
            order_description = sip_participant.attributes.get("order_description", order_description)
            order_value = sip_participant.attributes.get("order_value", order_value)
            external_call_id = sip_participant.attributes.get("external_call_id", external_call_id)
            break
        await asyncio.sleep(0.2)

    logger.info(f"Atributos extraídos: Cliente={client_name}, Descrição={order_description}, Valor={order_value}, ExternalCallID={external_call_id}")

    # Diretrizes de comportamento do assistente
    instructions = (
        "Você é o assistente virtual de voz da Sapataria Souza. "
        "Fale em português do Brasil de forma extremamente natural, curta e simpática (Speech-to-Speech). "
        "Evite responder com textos longos ou parágrafos grandes, pois o usuário está ouvindo você via chamada telefônica. "
        f"Os dados do cliente atual são: Nome: {client_name}, Pedido/Descrição: {order_description}, Valor: R$ {order_value}. "
        "Se o cliente perguntar sobre o valor do conserto, a descrição do serviço ou o nome dele, responda confirmando essas informações.\n\n"
        "Regras estritas de resposta a perguntas:\n"
        "- Se perguntado o horário de atendimento, responda que é de segunda a sexta-feira, das 09:00 às 18:00 (não abrimos aos sábados e domingos).\n"
        "- Se perguntar se fecha ao meio-dia, responda claramente que não fechamos ao meio-dia.\n"
        "- Se perguntado o endereço da loja, responda: Avenida Otto Niemeyer, 3210 - Cavalhada, Porto Alegre - RS.\n"
        "- Se o cliente disser obrigado ou demonstrar gratidão, responda gentilmente que está sempre à disposição sempre que precisar.\n"
        "Para qualquer outra pergunta fora de endereço, horário de atendimento, se fecha ao meio-dia ou dados do pedido, diga educadamente que não sabe responder no momento."
    )

    # Inicializa o modelo de voz em tempo real do Gemini (Gemini Live)
    model = RealtimeModel(
        model="gemini-2.5-flash-native-audio-preview-12-2025",
        voice="Puck",  # Puck é uma voz amigável
        instructions=instructions,
        temperature=0.7
    )

    session = AgentSession(llm=model)
    
    # Associa a sessão do LiveKit ao agente do Gemini Live
    await session.start(
        agent=Agent(instructions=instructions),
        room=ctx.room
    )
    logger.info("Sessão do agente Gemini Live iniciada!")

    # Primeira fala do agente: Cumprimenta, se identifica e avisa sobre o conserto pronto
    greeting_prompt = (
        f"Diga exatamente algo como: 'Olá, {client_name}! Aqui é o assistente virtual da Sapataria Souza. "
        f"Estou ligando para avisar que o seu/sua {order_description} já foi consertado(a) e está pronto(a) para retirada! "
        "Como posso ajudar você hoje?' Fale isso de forma muito natural e curta."
    )
    await session.generate_reply(instructions=greeting_prompt)

    start_time = asyncio.get_event_loop().time()
    try:
        # Loop para manter o entrypoint ativo enquanto a chamada estiver conectada
        while True:
            await asyncio.sleep(1)
    except asyncio.CancelledError:
        logger.info("Chamada encerrada pelo cliente ou sistema.")
    finally:
        end_time = asyncio.get_event_loop().time()
        duration = int(end_time - start_time)
        logger.info(f"Chamada finalizada. Duração: {duration} segundos.")

        # Constrói a transcrição a partir do histórico de chat do session
        transcription = ""
        if session.chat_ctx and session.chat_ctx.messages:
            for msg in session.chat_ctx.messages:
                if msg.role == "system":
                    continue
                role = "Agente" if msg.role == "assistant" else "Cliente"
                text = msg.text or ""
                if text:
                    transcription += f"[{role}]: {text}\n"

        if not transcription or transcription.strip() == "":
            transcription = "Ligação sem interação por voz."

        # Se temos o external_call_id, envia o webhook para o Laravel
        if external_call_id:
            app_url = os.getenv("APP_URL", "http://localhost:8000")
            laravel_url = os.getenv("LARAVEL_WEBHOOK_URL")
            if not laravel_url:
                if "localhost" in app_url:
                    # Se rodando no Docker compose, o container app/nginx responde na mesma rede
                    laravel_url = "http://nginx/api/webhook/n8n"
                else:
                    laravel_url = f"{app_url}/api/webhook/n8n"
            
            logger.info(f"Enviando webhook de finalização para {laravel_url}...")
            payload = {
                "external_call_id": external_call_id,
                "status_ligacao": "atendida",
                "duracao": duration,
                "transcricao_ia": transcription
            }
            
            try:
                async with aiohttp.ClientSession() as http_session:
                    async with http_session.post(laravel_url, json=payload, timeout=5) as response:
                        if response.status == 200:
                            logger.info("Webhook enviado com sucesso para o Laravel!")
                        else:
                            logger.error(f"Erro ao enviar webhook para o Laravel: {response.status} - {await response.text()}")
            except Exception as e:
                logger.error(f"Exceção ao enviar webhook para o Laravel: {e}")

if __name__ == "__main__":
    cli.run_app(WorkerOptions(entrypoint_fnc=entrypoint))
