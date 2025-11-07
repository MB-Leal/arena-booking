<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Elite Soccer</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Aplicando o mesmo gradiente usado na sua homepage */
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">


<!-- Container Centralizado (O Card Principal Transparente) -->
<div class="min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-7xl
        p-6 sm:p-10 lg:p-12
        bg-white/95 dark:bg-gray-800/90
        backdrop-blur-md shadow-2xl shadow-gray-900/70 dark:shadow-indigo-900/50
        rounded-3xl transform transition-all duration-300 ease-in-out">

        <h1 class="text-6xl font-extrabold text-gray-900 dark:text-gray-100 mb-10
            border-b-4 border-indigo-600 dark:border-indigo-400 pb-4 text-center
            tracking-tighter transform hover:scale-[1.005] transition duration-300">
            ⚽ ELITE SOCCER - Agendamento Online
        </h1>

        {{-- --- Mensagens de Status (Aprimoradas) --- --}}

        @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-600 text-green-800 dark:text-green-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="font-bold text-lg">SUCESSO!</span> <span class="ml-2">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <span class="font-bold text-lg">ERRO:</span> <span class="ml-2">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Alerta Geral de Erro de Submissão --}}
        @if ($errors->any() && !old('data_reserva'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-8 shadow-lg" role="alert">
                <p class="font-bold flex items-center text-lg"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg> Erro de Validação!</p>
                <p class="mt-1">Houve um problema com a sua seleção ou dados. Por favor, tente selecionar um horário novamente.</p>
            </div>
        @endif

        @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-400 dark:border-green-700 p-8 rounded-3xl relative mb-12 text-center shadow-2xl shadow-green-400/40 dark:shadow-green-900/70" role="alert">
                <p class="font-extrabold mb-3 text-4xl text-green-700 dark:text-green-300">✅ RESERVA PRÉ-APROVADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua vaga foi reservada por 30 minutos. **Clique abaixo imediatamente** para confirmar o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center px-12 py-5 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-105 active:scale-[0.98] uppercase tracking-wider text-xl">
                    <svg class="w-7 h-7 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.432 2.156A9.957 9.957 0 0010 0C4.477 0 0 4.477 0 10c0 1.956.685 3.766 1.83 5.216l-1.636 4.757a.75.75 0 00.974.974l4.757-1.636A9.957 9.097 0 0010 20c5.523 0 10-4.477 10-10 0-3.328-1.626-6.297-4.17-8.156zM8 14H6V8h2v6zm4 0h-2V8h2v6zm4 0h-2V8h2v6z"></path></svg>
                    ENVIAR COMPROVANTE VIA WHATSAPP
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o comprovante não for enviado.</p>
            </div>
        @endif


        {{-- ✅ Seleção de Data centralizada --}}
        <h2 class="text-5xl font-extrabold text-gray-900 dark:text-gray-100 mb-6 text-center tracking-tight">
            🗓️ Horários Disponíveis
        </h2>

        {{-- ESPAÇAMENTO AUMENTADO --}}
        <p class="text-gray-600 dark:text-gray-400 mb-20 text-center text-xl font-medium">
            Selecione o dia e o bloco de 1 hora disponível para sua partida:
        </p>

        {{-- Grade de Horários (Estilização Aprimorada para Telas Grandes) --}}
        @if (empty($weeklySchedule))
            <p class="text-center py-16 text-3xl text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-3xl shadow-inner border border-gray-300 dark:border-gray-700">
                Parece que todos os horários estão ocupados. 😔 Tente novamente na próxima semana.
            </p>
        @else
            {{-- ESPAÇAMENTO AUMENTADO --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6 mt-8">
                @foreach ($weeklySchedule as $dateString => $slots)
                    @php
                        $date = \Carbon\Carbon::parse($dateString);
                        // Garante que o Carbon localize o dia da semana em português
                        $date->setLocale('pt_BR');
                        $dayName = $date->isoFormat('dddd');
                        $formattedDate = $date->isoFormat('D/MM');
                    @endphp

                    {{-- Card de Dia da Semana --}}
                    <div class="bg-gray-50 dark:bg-gray-900 p-6 rounded-3xl shadow-2xl hover:shadow-indigo-500/50 dark:hover:shadow-indigo-700/60 transition-all duration-300
                         border-t-8 border-indigo-600 dark:border-indigo-500 flex flex-col h-full">

                        {{-- Cabeçalho do Dia --}}
                        <h3 class="text-3xl font-black text-indigo-800 dark:text-indigo-300 pb-3 mb-5 uppercase tracking-wider
                            border-b-2 border-indigo-200 dark:border-indigo-900">
                            {{ $dayName }}
                            <span class="text-base font-medium text-gray-500 dark:text-gray-400 ml-1">({{ $formattedDate }})</span>
                        </h3>

                        {{-- Slots de Horário --}}
                        <div class="space-y-4 flex-grow">
                            @forelse ($slots as $slot)
                                {{-- Botão com Estilo de Destaque --}}
                                <button
                                    type="button"
                                    class="open-modal w-full flex flex-col items-start sm:flex-row sm:justify-between sm:items-center p-4 rounded-2xl transition duration-300 group
                                        bg-white dark:bg-gray-800 border border-indigo-400 dark:border-indigo-700
                                        hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-700 transform hover:scale-[1.03] active:scale-[0.98]
                                        text-gray-900 dark:text-gray-100 font-bold shadow-lg hover:shadow-xl hover:shadow-indigo-400/40"
                                    data-date="{{ $dateString }}"
                                    data-start="{{ $slot['start_time'] }}"
                                    data-end="{{ $slot['end_time'] }}"
                                    data-price="{{ $slot['price'] }}"
                                    {{-- CRÍTICO: Adiciona o schedule_id ao botão --}}
                                    data-schedule-id="{{ $slot['schedule_id'] }}"
                                >
                                    {{-- Horário --}}
                                    <span class="text-2xl font-extrabold text-indigo-700 dark:text-indigo-300 group-hover:text-white transition duration-300 mb-1 sm:mb-0">
                                        {{ $slot['start_time'] }} - {{ $slot['end_time'] }}
                                    </span>
                                    {{-- Preço (Fundo fica branco no hover para garantir contraste) --}}
                                    <span class="font-extrabold text-sm bg-green-50 dark:bg-green-900/50 px-4 py-1.5 rounded-full border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 shadow-sm
                                        group-hover:bg-white group-hover:dark:bg-green-900/90 group-hover:text-green-800 group-hover:dark:text-green-300 transition duration-300">
                                        R$ {{ number_format($slot['price'], 2, ',', '.') }}
                                    </span>
                                </button>
                            @empty
                                <div class="text-center py-6 text-base text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-xl italic border border-dashed border-gray-300 dark:border-gray-600">
                                    <p>Dia Lotado! Sem horários.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif


        {{-- --- Modal de Confirmação de Dados --- --}}
        <div id="booking-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
            <div id="modal-content" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8
                @if ($errors->any() && old('data_reserva')) border-red-600 dark:border-red-500 @else border-indigo-600 dark:border-indigo-500 @endif">

                {{-- NOVO: Alerta de Erro Interno (se reabrir por falha de validação) --}}
                @if ($errors->any() && old('data_reserva'))
                    <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md" role="alert">
                        <p class="font-bold flex items-center text-lg">
                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            Correção Necessária!
                        </p>
                        <p class="mt-1">Por favor, verifique os campos destacados em vermelho e tente novamente.</p>
                    </div>
                @endif
                {{-- FIM NOVO ALERTA --}}

                {{-- INSTRUÇÃO DE PAGAMENTO (Estilo mais urgente) --}}
                <div class="mb-8 p-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 rounded-xl shadow-md dark:border-red-400 dark:text-red-200">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 mr-3 text-red-600 flex-shrink-0 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-black text-lg uppercase tracking-wider">Atenção!</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed font-semibold">
                        Sua vaga é garantida **apenas** após o **envio imediato do comprovante do sinal** via WhatsApp.
                    </p>
                </div>

                <h4 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3">Confirme Seus Dados</h4>

                {{-- Detalhes da Reserva (VISUAL APRIMORADO) --}}
                <div class="mb-8 p-6 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl border border-indigo-300 dark:border-indigo-700 shadow-xl">
                    <div class="space-y-4">
                        {{-- Data com mais espaço e borda --}}
                        <div class="flex justify-between items-center py-2 border-b border-indigo-100 dark:border-indigo-800">
                            <span class="font-medium text-lg text-indigo-800 dark:text-indigo-300">Data:</span>
                            <span id="modal-date" class="font-extrabold text-xl text-gray-900 dark:text-gray-100"></span>
                        </div>
                        {{-- Horário com mais espaço --}}
                        <div class="flex justify-between items-center py-2">
                            <span class="font-medium text-xl text-indigo-800 dark:text-indigo-300">Horário:</span>
                            <span id="modal-time" class="font-extrabold text-2xl text-gray-900 dark:text-gray-100"></span>
                        </div>
                    </div>
                    <hr class="border-indigo-200 dark:border-indigo-700 mt-4 mb-4">
                    {{-- Total com mais destaque --}}
                    <div class="flex justify-between items-center pt-2">
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">Total:</span>
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">R$ <span id="modal-price"></span></span>
                    </div>
                </div>

                <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                    @csrf

                    {{-- Campos Hidden: RENOMEADOS para corresponder ao StoreReservaRequest e adicionado schedule_id --}}
                    <input type="hidden" name="data_reserva" id="form-date" value="{{ old('data_reserva') }}">
                    <input type="hidden" name="hora_inicio" id="form-start" value="{{ old('hora_inicio') }}">
                    <input type="hidden" name="hora_fim" id="form-end" value="{{ old('hora_fim') }}">
                    <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">
                    <input type="hidden" name="schedule_id" id="form-schedule-id" value="{{ old('schedule_id') }}"> {{-- CRÍTICO: NOVO CAMPO --}}

                    <div class="mb-5">
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Seu Nome Completo</label>
                        {{-- Campo Nome: RENOMEADO para 'nome_cliente' --}}
                        <input type="text" name="nome_cliente" id="client_name" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('nome_cliente') border-red-500 ring-1 ring-red-500 @enderror"
                            value="{{ old('nome_cliente') }}">
                        {{-- Tag @error: CORRIGIDA para 'nome_cliente' --}}
                        @error('nome_cliente')
                            <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-8">
                        <label for="client_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Seu WhatsApp (apenas números)</label>
                        {{-- Campo Contato: RENOMEADO para 'contato_cliente' --}}
                        <input type="tel" name="contato_cliente" id="client_contact" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('contato_cliente') border-red-500 ring-1 ring-red-500 @enderror"
                            value="{{ old('contato_cliente') }}">
                        {{-- Tag @error: CORRIGIDA para 'contato_cliente' --}}
                        @error('contato_cliente')
                            <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                        @else
                            {{-- Placeholder para Mensagem de Validação do Cliente (Só aparece se não houver erro de backend) --}}
                            <p id="contact-validation-feedback" class="text-xs mt-1 font-semibold transition duration-300"></p>
                        @enderror
                    </div>


                    {{-- AÇÃO DOS BOTÕES: Aumentado pt-6 para pt-8, sm:space-x-6 (OK) e py-4 para py-5 --}}
                    <div class="flex flex-col sm:flex-row gap-4 justify-end space-y-4 sm:space-y-0 sm:space-x-6 pt-8 border-t dark:border-gray-700">
                        {{-- Botão Cancelar: Aumentado py-4 para py-5 --}}
                        <button type="button" id="close-modal" class="order-2 sm:order-1 p-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Voltar / Cancelar
                        </button>
                        {{-- Botão Enviar: Aumentado py-4 para py-5 --}}
                        <button type="submit" id="submit-booking-button" class="order-1 sm:order-2 p-4 bg-indigo-600 text-white font-extrabold rounded-full hover:bg-indigo-700 transition shadow-xl shadow-indigo-500/50 transform hover:scale-[1.03] active:scale-[0.97]">
                            Confirmar Pré-Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- Scripts para funcionalidade do modal e máscara (MANTIDOS) --}}
<script>
/**
 * Aplica máscara de telefone brasileiro (DDD + 8 ou 9 dígitos) no formato (XX) XXXXX-XXXX.
 * @param {string} value - Valor do input.
 * @returns {string} O valor mascarado.
 */
function maskWhatsapp(value) {
    // Remove tudo que não for dígito
    const digits = value.replace(/\D/g, "");
    const maxDigits = 11;
    const limitedDigits = digits.substring(0, maxDigits);
    let result = limitedDigits;

    // (XX) XXXXX-XXXX
    if (limitedDigits.length > 2) {
        result = `(${limitedDigits.substring(0, 2)}) ${limitedDigits.substring(2)}`;
    }
    // (XX) XXXXX-XXXX (11 digitos) ou (XX) XXXX-XXXX (10 digitos)
    if (limitedDigits.length > 6) {
        if (limitedDigits.length === 11) {
            // Se for 9 dígitos + 2 de DDD (Ex: 99999-9999)
            result = result.replace(/(\d{5})(\d{4})$/, "$1-$2");
        } else if (limitedDigits.length === 10) {
            // Se for 8 dígitos + 2 de DDD (Ex: 9999-9999)
            result = result.replace(/(\d{4})(\d{4})$/, "$1-$2");
        }
    }

    return result;
}

/**
 * Valida o número de telefone (10 ou 11 dígitos).
 * @param {string} value - Valor do input mascarado.
 * @returns {boolean} True se for válido (10 ou 11 dígitos), false caso contrário.
 */
function validateContact(value) {
    const digits = value.replace(/\D/g, "");
    // Considera válido se tiver 10 (DDD + 8 digitos) ou 11 (DDD + 9 digitos)
    return digits.length === 10 || digits.length === 11;
}

/**
 * Formata a data para o padrão Brasileiro (Dia da semana, dia de Mês de Ano).
 */
function formatarDataBrasileira(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    // Adicionado tratamento de erro para data inválida
    if (isNaN(date)) {
        return 'Data Inválida';
    }
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formatted = date.toLocaleDateString('pt-BR', options);
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
}


document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('booking-modal');
    const modalContent = document.getElementById('modal-content');
    const closeModalButton = document.getElementById('close-modal');

    // Campos do formulário
    const contactInput = document.getElementById('client_contact');
    const nameInput = document.getElementById('client_name');
    const submitButton = document.getElementById('submit-booking-button');
    // Elemento de feedback customizado (só existe se não houver erro de validação Laravel)
    const feedbackElement = document.getElementById('contact-validation-feedback');

    // Funções Blade injetadas (usando tags PHP no JS)
    // CRÍTICO: Usando para garantir sintaxe JS correta e evitar ParseErrors
    const oldDate = @json(old('data_reserva'));
    const oldStart = @json(old('hora_inicio'));
    const oldEnd = @json(old('hora_fim'));
    const oldPrice = @json(old('price')); // Valor numérico bruto
    const oldContactValue = @json(old('contato_cliente'));
    // É crucial capturar o schedule_id antigo também
    const oldScheduleId = @json(old('schedule_id'));

    /**
     * Atualiza o estado de validação do input de contato e do botão de envio.
     */
    function updateValidationState() {
        if (!contactInput || !submitButton) return;

        const isValid = validateContact(contactInput.value);
        // Checa se o Laravel retornou erro para 'contato_cliente'
        const hasBackendError = @json($errors->has("contato_cliente"));

        const canSubmit = isValid && !hasBackendError;
        submitButton.disabled = !canSubmit;
        submitButton.classList.toggle('opacity-50', !canSubmit);
        submitButton.classList.toggle('cursor-not-allowed', !canSubmit);


        // Atualizar Feedback Visual (apenas se não houver erro de backend)
        if (!hasBackendError && feedbackElement) {
            if (contactInput.value.length === 0) {
                feedbackElement.textContent = 'Aguardando 10 ou 11 dígitos (DDD + número).';
                feedbackElement.className = 'text-xs mt-1 font-semibold text-gray-500 dark:text-gray-400 transition duration-300';
            } else if (isValid) {
                feedbackElement.textContent = '✅ WhatsApp OK.';
                feedbackElement.className = 'text-xs mt-1 font-semibold text-green-600 dark:text-green-400 transition duration-300';
            } else {
                feedbackElement.textContent = '❌ Número incompleto ou formato incorreto (Ex: 99 999999999)';
                feedbackElement.className = 'text-xs mt-1 font-semibold text-red-600 dark:text-red-400 transition duration-300';
            }
        }
    }


    if (contactInput) {
        // Listener para aplicar máscara e validar em tempo real
        contactInput.addEventListener('input', (e) => {
            e.target.value = maskWhatsapp(e.target.value);
            updateValidationState();
        });

        // Aplica a máscara no valor 'old' se ele existir
        if (oldContactValue) {
            contactInput.value = maskWhatsapp(oldContactValue);
        }
    }


    // Abertura do Modal por clique nos horários
    document.querySelectorAll('.open-modal').forEach(button => {
        button.addEventListener('click', () => {
            const date = button.dataset.date;
            const start = button.dataset.start;
            const end = button.dataset.end;
            // O preço aqui é do dataset (numérico bruto) e formatado para display
            const priceRaw = button.dataset.price;
            const priceDisplay = parseFloat(priceRaw).toFixed(2).replace('.', ',');
            const scheduleId = button.dataset.scheduleId;

            // Popula o Modal com os dados visuais
            document.getElementById('modal-date').textContent = formatarDataBrasileira(date);
            document.getElementById('modal-time').textContent = `${start} - ${end}`;
            document.getElementById('modal-price').textContent = priceDisplay;

            // Popula os campos hidden do formulário
            document.getElementById('form-date').value = date;
            document.getElementById('form-start').value = start;
            document.getElementById('form-end').value = end;
            document.getElementById('form-price').value = priceRaw; // Crucial: valor bruto para o Controller
            document.getElementById('form-schedule-id').value = scheduleId;

            // Limpa campos de nome/contato (ou usa old se existirem)
            // Mantém o old() do nome apenas se for string não vazia.
            if (nameInput) nameInput.value = @json(old('nome_cliente'));
            if (contactInput) contactInput.value = oldContactValue ? maskWhatsapp(oldContactValue) : '';

            // Garante que o modal use a borda azul (padrão) ao ser aberto por clique
            modalContent.classList.remove('border-red-600', 'dark:border-red-500');
            modalContent.classList.add('border-indigo-600', 'dark:border-indigo-500');

            // O estado de validação inicial
            updateValidationState();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        });
    });

    // Reabrir Modal se a validação falhou e houver dados antigos
    if (oldDate && oldStart) {
        // Garante que o preço 'old' seja formatado corretamente para exibição
        const formattedOldPrice = parseFloat(oldPrice).toFixed(2).replace('.', ',');

        document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
        document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
        document.getElementById('modal-price').textContent = formattedOldPrice;
        // Popula o campo hidden do schedule_id
        document.getElementById('form-schedule-id').value = oldScheduleId;

        // O estado de validação será atualizado abaixo.

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // ATUALIZAÇÃO INICIAL do estado de validação após o DOM e os old() terem sido carregados
    if (contactInput) {
        updateValidationState();
    }


    // Fechar Modal
    closeModalButton.addEventListener('click', () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    });

    // Fechar Modal clicando fora
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
});
</script>

</body>
</html>
