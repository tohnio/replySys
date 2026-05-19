<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ReplySys - API e Sistema de Controle</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-50 text-gray-800 font-sans antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center p-4">
            <div class="bg-white p-10 rounded-xl shadow-lg max-w-2xl w-full text-center">
                <div class="flex justify-center mb-6">
                    <svg class="w-16 h-16 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h1 class="text-4xl font-bold text-indigo-900 mb-4">ReplySys</h1>
                <p class="text-lg text-gray-600 mb-8">
                    Sistema de Controle de Itens Reparados e Notificação Automática de Clientes via IA.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ url('/api/documentation') }}" class="block p-6 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition duration-200">
                        <h2 class="text-xl font-semibold text-indigo-700 mb-2">📚 Documentação API</h2>
                        <p class="text-sm text-indigo-600">Acesse o Swagger UI para testar e integrar com o App Mobile.</p>
                    </a>

                    <a href="{{ url('/dashboard') }}" class="block p-6 bg-emerald-50 border border-emerald-100 rounded-lg hover:bg-emerald-100 transition duration-200">
                        <h2 class="text-xl font-semibold text-emerald-700 mb-2">⚙️ Painel de Controle</h2>
                        <p class="text-sm text-emerald-600">Acesse a área administrativa para visualizar as manutenções.</p>
                    </a>
                </div>

                <div class="mt-10 text-sm text-gray-500">
                    Desenvolvido com Laravel v{{ Illuminate\Foundation\Application::VERSION }}
                </div>
            </div>
        </div>
    </body>
</html>
