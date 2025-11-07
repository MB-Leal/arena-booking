<x-app-layout>
    {{-- Assume-se que você está usando um layout chamado 'app-layout' ou similar --}}
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalhes da Reserva #{{ $reserva->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            {{-- Mensagens de feedback (sucesso/erro) --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-8">
                <div class="flex justify-between items-start mb-6 border-b pb-4">
                    <div>
                        <h3 class="text-2xl font-bold text-gray-900">
                            Reserva para {{ $reserva->client_name }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Criado em: {{ $reserva->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <span class="px-3 py-1 text-sm font-semibold rounded-full
                        @if($reserva->status == \App\Models\Reserva::STATUS_CONFIRMADA) bg-green-100 text-green-800
                        @elseif($reserva->status == \App\Models\Reserva::STATUS_PENDENTE) bg-yellow-100 text-yellow-800
                        @elseif($reserva->status == \App\Models\Reserva::STATUS_CANCELADA) bg-red-100 text-red-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        Status: {{ strtoupper($reserva->status) }}
                    </span>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Data e Hora --}}
                        <div class="detail-box">
                            <p class="text-sm font-medium text-gray-500">Data e Hora</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                das {{ $reserva->start_time }} às {{ $reserva->end_time }}
                            </p>
                        </div>

                        {{-- Cliente (Registrado ou Manual) --}}
                        <div class="detail-box">
                            <p class="text-sm font-medium text-gray-500">Cliente</p>
                            <p class="mt-1 text-lg font-semibold text-gray-900">
                                @if ($reserva->user)
                                    {{ $reserva->user->name }} (Reg.)
                                @else
                                    {{ $reserva->client_name }} (Manual)
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        {{-- Contato --}}
                        <div class="detail-box">
                            <p class="text-sm font-medium text-gray-500">Contato</p>
                            <p class="mt-1 text-base text-gray-900">{{ $reserva->client_contact ?? 'N/A' }}</p>
                        </div>

                        {{-- Preço --}}
                        <div class="detail-box">
                            <p class="text-sm font-medium text-gray-500">Preço</p>
                            <p class="mt-1 text-lg font-semibold text-indigo-600">
                                R$ {{ number_format($reserva->price, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Notas/Observações --}}
                    <div class="detail-box bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm font-medium text-gray-500">Notas/Observações</p>
                        <p class="mt-1 text-base text-gray-900 whitespace-pre-wrap">
                            {{ $reserva->notes ?? 'Nenhuma observação fornecida.' }}
                        </p>
                    </div>

                    {{-- Gestor Responsável pela Ação --}}
                    @if ($reserva->manager_id)
                        <div class="detail-box">
                            <p class="text-sm font-medium text-gray-500">Gerida por</p>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ $reserva->manager->name ?? 'Gestor Desconhecido' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- GESTÃO DE AÇÕES --}}
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h4 class="text-xl font-bold mb-4 border-b pb-2">Ações de Gestão</h4>

                <div class="flex flex-wrap gap-3">
                    {{-- 1. Formulário de Confirmação --}}
                    @if($reserva->status != \App\Models\Reserva::STATUS_CONFIRMADA)
                        <form action="{{ route('admin.reservas.updateStatus', $reserva) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Confirmar Reserva
                            </button>
                        </form>
                    @endif

                    {{-- 2. Formulário de Rejeição (Apenas se não estiver cancelada/rejeitada e não confirmada) --}}
                    @if($reserva->status == \App\Models\Reserva::STATUS_PENDENTE)
                        <form action="{{ route('admin.reservas.updateStatus', $reserva) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="rejected">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Rejeitar Pré-Reserva
                            </button>
                        </form>
                    @endif

                    {{-- 3. Formulário de Cancelamento (Se estiver confirmada) --}}
                    @if($reserva->status == \App\Models\Reserva::STATUS_CONFIRMADA)
                        <form action="{{ route('admin.reservas.updateStatus', $reserva) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 active:bg-yellow-700 focus:outline-none focus:border-yellow-700 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Cancelar Reserva
                            </button>
                        </form>
                    @endif

                    {{-- 4. Formulário de Exclusão (Perigo!) --}}
                    <form id="delete-form-{{ $reserva->id }}" action="{{ route('admin.reservas.destroy', $reserva) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button" onclick="confirmDelete()" class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                            Excluir Permanentemente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para a exclusão (simulando um confirm com JavaScript simples) --}}
    <script>
        function confirmDelete() {
            // OBS: Como não podemos usar window.confirm() em iframes,
            // esta função deve ser substituída por um modal de confirmação.
            // Para simular a intenção, usaremos um prompt simples.
            const confirmation = prompt("ATENÇÃO: Digite 'EXCLUIR' para confirmar a exclusão permanente desta reserva.");

            if (confirmation === 'EXCLUIR') {
                document.getElementById('delete-form-{{ $reserva->id }}').submit();
            } else {
                // Mensagem de erro ou cancelamento
                console.log('Exclusão cancelada ou confirmação incorreta.');
            }
        }
    </script>
    <style>
        .detail-box {
            padding: 0.5rem 0;
        }
        .detail-box p:last-child {
            margin-top: 0.25rem;
        }
    </style>
</x-app-layout>
