<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "ReplySys API",
    description: "API para Controle de Itens Reparados e Integração de Chamadas IA (Vapi)"
)]
#[OA\Server(
    url: "/api",
    description: "API Server"
)]
class OsController extends Controller
{
    #[OA\Get(
        path: "/ordens-servico",
        summary: "Obter lista de Ordens de Serviço",
        tags: ["Ordens de Servico"],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index()
    {
        return response()->json(\App\Models\OrdemServico::with('cliente')->get());
    }

    #[OA\Put(
        path: "/ordens-servico/{id}/status",
        summary: "Atualiza o status da OS",
        description: "Usado pelo App genérico para modificar o status. Dispara a ligação IA se for REPARADO.",
        tags: ["Ordens de Servico"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["status"],
                properties: [
                    new OA\Property(property: "status", type: "string", enum: ["RECEBIDO", "EM_REPARO", "AGUARDANDO_PECA", "REPARADO", "ENTREGUE"])
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Status atualizado")
        ]
    )]
    public function updateStatus(\Illuminate\Http\Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:RECEBIDO,EM_REPARO,AGUARDANDO_PECA,REPARADO,ENTREGUE'
        ]);

        $os = \App\Models\OrdemServico::findOrFail($id);
        $statusAnterior = $os->status;
        
        $os->status = $request->status;
        
        if ($request->status === 'REPARADO' && $statusAnterior !== 'REPARADO') {
            $os->data_reparo = now();
            $os->save();
            if ($os->cliente && !empty($os->cliente->telefone)) {
                \App\Jobs\WhatsAppNotificationJob::dispatch($os);
            }
        } else if ($request->status === 'ENTREGUE' && $statusAnterior !== 'ENTREGUE') {
            $os->data_entregue = now();
            $os->save();
        } else {
            $os->save();
        }

        return response()->json(['message' => 'Status atualizado com sucesso!', 'os' => $os]);
    }

    #[OA\Post(
        path: "/ordens-servico",
        summary: "Cadastra nova Ordem de Serviço",
        tags: ["Ordens de Servico"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["descricao_item", "nome", "telefone"],
                properties: [
                    new OA\Property(property: "descricao_item", type: "string"),
                    new OA\Property(property: "defeito_relatado", type: "string"),
                    new OA\Property(property: "modelo", type: "string"),
                    new OA\Property(property: "data_pronto", type: "string", format: "date"),
                    new OA\Property(property: "nome", type: "string"),
                    new OA\Property(property: "telefone", type: "string"),
                    new OA\Property(property: "valor", type: "number", format: "float"),
                    new OA\Property(property: "status_pagamento", type: "string", enum: ["total", "parcial", "pendente"]),
                    new OA\Property(property: "valor_pago", type: "number", format: "float")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "OS Criada")
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao_item' => 'nullable|string',
            'defeito_relatado' => 'nullable|string',
            'modelo' => 'nullable|string',
            'data_pronto' => 'nullable|date',
            'nome' => 'required|string',
            'telefone' => 'nullable|string',
            'valor' => 'nullable|numeric',
            'status_pagamento' => 'nullable|string|in:total,parcial,pendente',
            'valor_pago' => 'nullable|numeric'
        ]);

        $telefone = $data['telefone'] ?? '';
        if (!empty($telefone)) {
            $cliente = \App\Models\Cliente::where('telefone', $telefone)->first();
            if (!$cliente) {
                $cliente = \App\Models\Cliente::create([
                    'nome' => $data['nome'],
                    'telefone' => $telefone
                ]);
            }
        } else {
            $cliente = \App\Models\Cliente::create([
                'nome' => $data['nome'],
                'telefone' => ''
            ]);
        }

        $os = \App\Models\OrdemServico::create([
            'cliente_id' => $cliente->id,
            'descricao_item' => $data['modelo'] ?? 'Equipamento',
            'defeito_relatado' => $data['defeito_relatado'] ?? '',
            'modelo' => $data['modelo'] ?? null,
            'data_pronto' => $data['data_pronto'] ?? null,
            'valor_orcamento' => $data['valor'] ?? 0,
            'status_pagamento' => $data['status_pagamento'] ?? 'pendente',
            'valor_pago' => $data['valor_pago'] ?? 0,
            'status' => 'RECEBIDO',
            'data_entrada' => now()
        ]);

        return response()->json(['message' => 'OS Cadastrada com sucesso!', 'os' => $os], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    #[OA\Put(
        path: "/ordens-servico/{id}",
        summary: "Atualiza uma Ordem de Serviço existente",
        tags: ["Ordens de Servico"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nome"],
                properties: [
                    new OA\Property(property: "descricao_item", type: "string"),
                    new OA\Property(property: "defeito_relatado", type: "string"),
                    new OA\Property(property: "modelo", type: "string"),
                    new OA\Property(property: "data_pronto", type: "string", format: "date"),
                    new OA\Property(property: "nome", type: "string"),
                    new OA\Property(property: "telefone", type: "string"),
                    new OA\Property(property: "valor", type: "number", format: "float"),
                    new OA\Property(property: "status_pagamento", type: "string", enum: ["total", "parcial", "pendente"]),
                    new OA\Property(property: "valor_pago", type: "number", format: "float")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "OS atualizada com sucesso")
        ]
    )]
    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'descricao_item' => 'nullable|string',
            'defeito_relatado' => 'nullable|string',
            'modelo' => 'nullable|string',
            'data_pronto' => 'nullable|date',
            'nome' => 'required|string',
            'telefone' => 'nullable|string',
            'valor' => 'nullable|numeric',
            'status_pagamento' => 'nullable|string|in:total,parcial,pendente',
            'valor_pago' => 'nullable|numeric'
        ]);

        $os = \App\Models\OrdemServico::findOrFail($id);
        
        $cliente = $os->cliente;
        if ($cliente) {
            $cliente->nome = $data['nome'];
            $cliente->telefone = $data['telefone'] ?? '';
            $cliente->save();
        }

        $os->update([
            'descricao_item' => $data['modelo'] ?? $os->descricao_item,
            'defeito_relatado' => $data['defeito_relatado'] ?? $os->defeito_relatado,
            'modelo' => $data['modelo'] ?? $os->modelo,
            'data_pronto' => $data['data_pronto'] ?? $os->data_pronto,
            'valor_orcamento' => $data['valor'] ?? $os->valor_orcamento,
            'status_pagamento' => $data['status_pagamento'] ?? $os->status_pagamento,
            'valor_pago' => $data['valor_pago'] ?? $os->valor_pago,
        ]);

        return response()->json(['message' => 'OS atualizada com sucesso!', 'os' => $os->load('cliente')]);
    }

    #[OA\Post(
        path: "/ordens-servico/{id}/redial",
        summary: "Dispara uma chamada telefônica manualmente",
        description: "Dispara o CallCustomerJob se a OS estiver no status REPARADO.",
        tags: ["Ordens de Servico"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Ligação iniciada com sucesso"),
            new OA\Response(response: 400, description: "Ordem de serviço não elegível")
        ]
    )]
    public function redial($id)
    {
        $os = \App\Models\OrdemServico::findOrFail($id);
        
        if ($os->status !== 'REPARADO') {
            return response()->json(['status' => 'error', 'message' => 'Ordem de serviço não está no status REPARADO.'], 400);
        }

        if (empty($os->cliente->telefone)) {
            return response()->json(['status' => 'error', 'message' => 'Cliente não possui telefone cadastrado.'], 400);
        }

        \App\Jobs\CallCustomerJob::dispatch($os);

        return response()->json(['status' => 'success', 'message' => 'Ligação disparada com sucesso!']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
