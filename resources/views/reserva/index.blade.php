<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Online</title>

    {{-- Tailwind CSS & JS (assumindo que o vite as carrega) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- FullCalendar Imports --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />

    <style>
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
        .calendar-container {
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .fc {
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-overlay.hidden {
            display: none !important;
        }
        /* Estilo para Eventos Disponíveis (Verde) */
        .fc-event-available {
            background-color: #10B981 !important; /* Verde 500 */
            border-color: #059669 !important;
            color: white !important;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            opacity: 0.9;
            transition: opacity 0.2s;
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

        <h1 class="text-5xl font-extrabold text-gray-900 dark:text-gray-100 mb-8
            border-b-4 border-indigo-600 dark:border-indigo-400 pb-4 text-center
            tracking-tighter transform hover:scale-[1.005] transition duration-300">
            ⚽ ELITE SOCCER - Agendamento Online
        </h1>

        <p class="text-gray-600 dark:text-gray-400 mb-10 text-center text-xl font-medium">
            Selecione uma data no calendário abaixo e clique nos horários **Verdes** disponíveis para fazer sua pré-reserva.
        </p>

        {{-- --- Mensagens de Status --- --}}

        @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-600 text-green-800 dark:text-green-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <span class="font-bold text-lg">SUCESSO!</span> <span class="ml-2">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-400 dark:border-green-700 p-8 rounded-3xl relative mb-12 text-center shadow-2xl shadow-green-400/40 dark:shadow-green-900/70" role="alert">
                <p class="font-extrabold mb-3 text-4xl text-green-700 dark:text-green-300">✅ RESERVA PRÉ-APROVADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua vaga foi reservada por 30 minutos. **Clique abaixo imediatamente** para confirmar o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center p-4 px-12 py-5 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-105 active:scale-[0.97] uppercase tracking-wider text-xl">
                    ENVIAR COMPROVANTE VIA WHATSAPP
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o comprovante não for enviado.</p>
            </div>
        @endif

        {{-- Alerta Geral de Erro de Submissão (incluindo erro de conflito) --}}
        @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <span class="font-bold text-lg">ERRO:</span> <span class="ml-2">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-8 shadow-lg" role="alert">
                <p class="font-bold flex items-center text-lg"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg> Erro de Validação!</p>
                <p class="mt-1">Houve um problema com a sua seleção ou dados. Por favor, verifique os campos destacados no formulário abaixo.</p>
            </div>
        @endif

        {{-- Calendário FullCalendar --}}
        <div class="calendar-container shadow-2xl">
            <div id='calendar'></div>
        </div>

    </div>
</div>

{{-- --- Modal de Confirmação de Dados --- --}}
<div id="booking-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div id="modal-content" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8
        @if ($errors->any() && old('data_reserva')) border-red-600 dark:border-red-500 @else border-indigo-600 dark:border-indigo-500 @endif">

        @if ($errors->any() && old('data_reserva'))

            @if ($errors->has('reserva_conflito_id'))
                {{-- ✅ Mensagem explícita de CONFLITO/VAGA OCUPADA (Amarelo) --}}
                <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 rounded-xl relative shadow-md" role="alert">
                    <p class="font-bold flex items-center text-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Vaga Ocupada!
                    </p>
                    <p class="mt-1 font-semibold">
                        Este horário **acabou de ser reservado** por outro cliente ou está em conflito. Por favor, feche o modal e escolha um slot verde diferente no calendário.
                    </p>
                </div>
            @else
                {{-- Mensagem padrão de Validação (Vermelho) --}}
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md" role="alert">
                    <p class="font-bold flex items-center text-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                        Correção Necessária!
                    </p>
                    <p class="mt-1">
                        Por favor, verifique os campos destacados em vermelho e tente novamente.
                    </p>
                </div>
            @endif

        @endif

        {{-- ✅ Alerta para Erros de Validação Front-End --}}
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md hidden" role="alert" id="frontend-alert-box">
            <p id="frontend-alert-message" class="font-bold flex items-center text-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                <span class="text-base">Atenção</span>: <span class="ml-1 text-sm font-normal"></span>
            </p>
        </div>


        <div class="mb-8 p-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 rounded-xl shadow-md dark:border-red-400 dark:text-red-200">
            <div class="flex items-center mb-2">
                <svg class="w-6 h-6 mr-3 text-red-600 flex-shrink-0 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <p class="font-black text-lg uppercase tracking-wider">Atenção!</p>
            </div>
            <p class="mt-2 text-sm leading-relaxed font-semibold">
                Sua vaga é garantida **apenas** após o **envio imediato do comprovante do sinal** via WhatsApp.
            </p>
        </div>

        <h4 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3">Confirme Seus Dados</h4>

        <div class="mb-8 p-6 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl border border-indigo-300 dark:border-indigo-700 shadow-xl">
            <div class="space-y-4">
                <div class="flex justify-between items-center py-2 border-b border-indigo-100 dark:border-indigo-800">
                    <span class="font-medium text-lg text-indigo-800 dark:text-indigo-300">Data:</span>
                    <span id="modal-date" class="font-extrabold text-xl text-gray-900 dark:text-gray-100"></span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="font-medium text-xl text-indigo-800 dark:text-indigo-300">Horário:</span>
                    <span id="modal-time" class="font-extrabold text-2xl text-gray-900 dark:text-gray-100"></span>
                </div>
            </div>
            <hr class="border-indigo-200 dark:border-indigo-700 mt-4 mb-4">
            <div class="flex justify-between items-center pt-2">
                <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">Total:</span>
                <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">R$ <span id="modal-price"></span></span>
            </div>
        </div>

        <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
            @csrf

            {{-- Campos Hidden --}}
            <input type="hidden" name="data_reserva" id="form-date" value="{{ old('data_reserva') }}">
            <input type="hidden" name="hora_inicio" id="form-start" value="{{ old('hora_inicio') }}">
            <input type="hidden" name="hora_fim" id="form-end" value="{{ old('hora_fim') }}">
            <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">
            {{-- CRÍTICO: Campo oculto para o erro de CONFLITO --}}
            <input type="hidden" name="reserva_conflito_id" value="" />
           
            {{-- 🛑 DEBUG VISUAL: Este é o campo que deve conter o ID do slot fixo. Agora está visível para debug! --}}
            <div class="mb-5 p-3 bg-yellow-50 rounded-lg border border-yellow-300">
                <label for="form-schedule-id" class="block text-xs font-semibold text-yellow-800 mb-1">
                    [DEBUG] ID do Slot Selecionado (schedule_id):
                </label>
                <input type="text" name="schedule_id" id="form-schedule-id" value="{{ old('schedule_id') }}" readonly
                    class="mt-1 block w-full text-sm font-mono border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md bg-white">
            </div>
            {{-- FIM DO DEBUG --}}


            <div class="mb-5">
                <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Seu Nome Completo</label>
                <input type="text" name="nome_cliente" id="client_name"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('nome_cliente') border-red-500 ring-1 ring-red-500 @enderror"
                    value="{{ old('nome_cliente') }}">
                @error('nome_cliente')
                    <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label for="client_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Seu WhatsApp (apenas números, com DDD) *
                </label>
                <input type="tel" name="contato_cliente" id="client_contact"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('contato_cliente') border-red-500 ring-1 ring-red-500 @enderror"
                    value="{{ old('contato_cliente') }}"
                    inputmode="numeric"
                    maxlength="15"
                    pattern="\d{10,11}"
                    placeholder="Ex: 91985320997">
                @error('contato_cliente')
                    <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                @else
                    <p id="contact-validation-feedback" class="text-xs mt-1 font-semibold transition duration-300"></p>
                @enderror
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-end space-y-4 sm:space-y-0 sm:space-x-6 pt-8 border-t dark:border-gray-700">
                <button type="button" id="close-modal" class="order-2 sm:order-1 p-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Voltar / Cancelar
                </button>
                <button type="button" id="submit-booking-button" class="order-1 sm:order-2 p-4 bg-indigo-600 text-white font-extrabold rounded-full hover:bg-indigo-700 transition shadow-xl shadow-indigo-500/50 transform hover:scale-[1.03] active:scale-[0.97]">
                    Confirmar Pré-Reserva
                </button>
            </div>
        </form>
       
        {{-- Log de Validação do schedule_id no front-end --}}
        <p id="schedule-id-debug-info" class="text-xs mt-4 text-center text-gray-400 dark:text-gray-500"></p>
    </div>
</div>

{{-- FullCalendar, Moment.js e Scripts Customizados --}}
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>


<script>
    // 🛑 CRÍTICO: Rota API para buscar os horários disponíveis (slots verdes)
    const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';

    /**
     * Aplica máscara de telefone brasileiro (DDD + 8 ou 9 dígitos) no formato (XX) XXXXX-XXXX.
     */
    function maskWhatsapp(value) {
        const digits = value.replace(/\D/g, "");
        const maxDigits = 11;
        const limitedDigits = digits.substring(0, maxDigits);
        let result = limitedDigits;

        if (limitedDigits.length > 2) {
            result = `(${limitedDigits.substring(0, 2)}) ${limitedDigits.substring(2)}`;
        }
        if (limitedDigits.length > 6) {
            if (limitedDigits.length === 11) {
                result = result.replace(/(\d{5})(\d{4})$/, "$1-$2");
            } else if (limitedDigits.length === 10) {
                result = result.replace(/(\d{4})(\d{4})$/, "$1-$2");
            }
        }

        return result;
    }

    /**
     * Valida o número de telefone (10 ou 11 dígitos).
     */
    function validateContact(value) {
        const digits = value.replace(/\D/g, "");
        return digits.length === 10 || digits.length === 11;
    }

    /**
     * Formata a data para o padrão Brasileiro (Dia da semana, dia de Mês de Ano).
     */
    function formatarDataBrasileira(dateString) {
        // FullCalendar usa formato ISO sem fuso horário. A hora 'T00:00:00' evita desvios.
        const date = new Date(dateString + 'T00:00:00');
        if (isNaN(date)) {
            return 'Data Inválida';
        }
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formatted = date.toLocaleDateString('pt-BR', options);
        // Capitaliza a primeira letra do dia da semana
        return formatted.charAt(0).toUpperCase() + formatted.slice(1);
    }


    document.addEventListener('DOMContentLoaded', () => {

        const calendarEl = document.getElementById('calendar');
        const modal = document.getElementById('booking-modal');
        const modalContent = document.getElementById('modal-content');
        const closeModalButton = document.getElementById('close-modal');
        const bookingForm = document.getElementById('booking-form');

        // Campos do formulário e validação
        const contactInput = document.getElementById('client_contact');
        const nameInput = document.getElementById('client_name');
        const submitButton = document.getElementById('submit-booking-button');
        const feedbackElement = document.getElementById('contact-validation-feedback');
        const scheduleIdInput = document.getElementById('form-schedule-id'); // 🛑 NOVO
        const scheduleIdDebugInfo = document.getElementById('schedule-id-debug-info'); // 🛑 NOVO

        // Elementos do Novo Alerta Front-End
        const frontendAlertBox = document.getElementById('frontend-alert-box');
        const frontendAlertMessage = document.getElementById('frontend-alert-message').querySelector('span.ml-1.text-sm.font-normal');


        // Dados antigos (para reabrir modal em caso de erro de validação)
        const oldDate = @json(old('data_reserva'));
        const oldStart = @json(old('hora_inicio'));
        const oldEnd = @json(old('hora_fim'));
        const oldPrice = @json(old('price'));
        const oldContactValue = @json(old('contato_cliente'));
        const oldScheduleId = @json(old('schedule_id'));


        /**
         * Exibe um alerta temporário no modal (Substitui alert()).
         */
        function showFrontendAlert(message) {
            if (frontendAlertBox && frontendAlertMessage) {
                frontendAlertMessage.textContent = message;
                frontendAlertBox.classList.remove('hidden');
                // Oculta após 5 segundos
                setTimeout(() => {
                    frontendAlertBox.classList.add('hidden');
                }, 5000);
            }
        }


        /**
         * Atualiza o estado de validação do input de contato e do botão de envio.
         */
        function updateValidationState() {
            if (!contactInput || !nameInput || !submitButton || !scheduleIdInput) return;

            const isValidContact = validateContact(contactInput.value);
            const nameIsFilled = nameInput.value.trim().length > 0;

            const hasBackendError = @json($errors->has("contato_cliente"));
            const hasNameBackendError = @json($errors->has("nome_cliente"));
           
            // CRÍTICO: Checa se o schedule_id foi preenchido
            const scheduleIdFilled = scheduleIdInput.value.length > 0;
            scheduleIdDebugInfo.textContent = scheduleIdFilled ? `ID do Slot: ${scheduleIdInput.value} (OK)` : 'ID do Slot: Vazio (ERRO)';


            const canSubmit = isValidContact && nameIsFilled && scheduleIdFilled && !hasBackendError && !hasNameBackendError;


            submitButton.disabled = !canSubmit;
            submitButton.classList.toggle('opacity-50', !canSubmit);
            submitButton.classList.toggle('cursor-not-allowed', !canSubmit);

            // Feedback visual para nome
            if (nameInput.value.trim().length === 0) {
                nameInput.classList.add('ring-2', 'ring-yellow-500/50');
            } else {
                nameInput.classList.remove('ring-2', 'ring-yellow-500/50');
            }

            // Atualizar Feedback Visual do Contato
            if (!hasBackendError && feedbackElement) {
                if (contactInput.value.length === 0) {
                    feedbackElement.textContent = 'Aguardando 10 ou 11 dígitos (DDD + número).';
                    feedbackElement.className = 'text-xs mt-1 font-semibold text-gray-500 dark:text-gray-400 transition duration-300';
                } else if (isValidContact) {
                    feedbackElement.textContent = '✅ WhatsApp OK.';
                    feedbackElement.className = 'text-xs mt-1 font-semibold text-green-600 dark:text-green-400 transition duration-300';
                } else {
                    feedbackElement.textContent = '❌ Número incompleto ou formato incorreto (Ex: 99 999999999)';
                    feedbackElement.className = 'text-xs mt-1 font-semibold text-red-600 dark:text-red-400 transition duration-300';
                }
            }
        }

        // === Event Listeners de Validação e Máscara ===
        if (contactInput) {
            contactInput.addEventListener('input', (e) => {
                e.target.value = maskWhatsapp(e.target.value);
                updateValidationState();
            });

            // Limpa a máscara quando o usuário sai do campo para evitar falhas de validação.
            contactInput.addEventListener('blur', (e) => {
                const digitsOnly = e.target.value.replace(/\D/g, "");
                // Re-aplica a máscara, mas apenas se o campo estiver completo e válido
                if(digitsOnly.length === 10 || digitsOnly.length === 11) {
                    e.target.value = maskWhatsapp(digitsOnly);
                } else {
                    // Se estiver incompleto, apenas exibe a versão atual para o usuário corrigir.
                    e.target.value = digitsOnly;
                }
                updateValidationState();
            });


            if (oldContactValue) {
                // Re-aplica a máscara ao valor antigo
                contactInput.value = maskWhatsapp(oldContactValue);
            }
        }

        if (nameInput) {
            nameInput.addEventListener('input', updateValidationState);
        }

        // 🛑 Listener de Submissão Manual (limpeza do contato)
        submitButton.addEventListener('click', (event) => {
            event.preventDefault();

            const isValidContact = validateContact(contactInput.value);
            const nameIsFilled = nameInput.value.trim().length > 0;
            const scheduleIdFilled = scheduleIdInput.value.length > 0;


            if (!isValidContact || !nameIsFilled || !scheduleIdFilled) {
                updateValidationState();

                // ✅ NOVO: Substituição do alert() por showFrontendAlert()
                if (!scheduleIdFilled) {
                     showFrontendAlert("❌ Seleção do horário perdida. Por favor, feche o modal e clique no slot verde novamente.");
                } else if (!nameIsFilled) {
                     showFrontendAlert("❌ Por favor, preencha seu nome completo.");
                } else if (!isValidContact) {
                     showFrontendAlert("❌ O número de WhatsApp está incompleto ou incorreto.");
                }

                return;
            }

            // LIMPEZA FINAL: Remove máscara e espaços para envio ao backend
            const maskedValue = contactInput.value;
            const digitsOnly = maskedValue.trim().replace(/\D/g, "");

            // Atribui apenas os dígitos ao campo ANTES da submissão
            contactInput.value = digitsOnly;

            // Submissão
            bookingForm.submit();
        });

        // Fechar Modal
        closeModalButton.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (contactInput) {
                // Garante que o valor mascarado seja reintroduzido após fechar
                contactInput.value = maskWhatsapp(contactInput.value);
            }
        });

        // Fechar Modal clicando fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (contactInput) {
                    // Garante que o valor mascarado seja reintroduzido após fechar
                    contactInput.value = maskWhatsapp(contactInput.value);
                }
            }
        });


        // === Inicialização do FullCalendar ===
        let calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            height: 'auto',
            timeZone: 'local',

            // 🛑 CRÍTICO: USA APENAS OS SLOTS DISPONÍVEIS
            eventSources: [
                {
                    url: AVAILABLE_API_URL,
                    method: 'GET',
                    failure: function() {
                        // 🛑 Mensagem de erro mais detalhada para debug
                        console.error('Falha na API de Horários Disponíveis. URL: ' + AVAILABLE_API_URL);
                    },
                    className: 'fc-event-available',
                    display: 'block'
                }
            ],

            views: {
                dayGridMonth: { buttonText: 'Mês' },
                timeGridWeek: { buttonText: 'Semana' },
                timeGridDay: { buttonText: 'Dia' }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            editable: false,
            initialDate: new Date().toISOString().slice(0, 10),

            eventClick: function(info) {
                const event = info.event;
                const isAvailable = event.classNames.includes('fc-event-available');

                // --- LÓGICA DE SLOT DISPONÍVEL ---
                if (isAvailable && event.extendedProps && event.extendedProps.is_fixed) {

                    // CRÍTICO: Limpa o alerta front-end ao abrir o modal
                    frontendAlertBox.classList.add('hidden');


                    const startDate = moment(event.start);
                    const endDate = moment(event.end);

                    const dateString = startDate.format('YYYY-MM-DD');
                    const startTimeInput = startDate.format('H:mm');
                    const endTimeInput = endDate.format('H:mm');
                    const timeSlotDisplay = startTimeInput + ' - ' + endTimeInput;

                    const extendedProps = event.extendedProps || {};
                    const priceRaw = extendedProps.price || 0;
                    const priceDisplay = parseFloat(priceRaw).toFixed(2).replace('.', ',');

                    // O ID do slot fixo é o ID da Reserva Fixa
                    const scheduleId = event.id;

                    // 1. Popula o Modal VISUAL
                    document.getElementById('modal-date').textContent = formatarDataBrasileira(dateString);
                    document.getElementById('modal-time').textContent = timeSlotDisplay;
                    document.getElementById('modal-price').textContent = priceDisplay;

                    // 2. Popula os campos HIDDEN/DEBUG do formulário para submissão
                    document.getElementById('form-date').value = dateString;
                    document.getElementById('form-start').value = startTimeInput;
                    document.getElementById('form-end').value = endTimeInput;
                    document.getElementById('form-price').value = priceRaw;
                   
                    // CRÍTICO: Popula o schedule_id com o ID do evento (que é o ID da Reserva Fixa)
                    scheduleIdInput.value = scheduleId;

                    // 3. Limpa campos de nome/contato (preparando para o cliente preencher)
                    // Se houver erro de validação (old data), não limpa
                    if (!oldDate) {
                        nameInput.value = '';
                        contactInput.value = '';
                    }
                   
                    updateValidationState();

                    // 4. Abrir o modal
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            }
        });

        calendar.render();

        // === Lógica de Reabertura do Modal em caso de Erro de Validação ===
        if (oldDate && oldStart) {
            const formattedOldPrice = parseFloat(oldPrice).toFixed(2).replace('.', ',');

            document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
            document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
            document.getElementById('modal-price').textContent = formattedOldPrice;
            scheduleIdInput.value = oldScheduleId; // Garante que o ID do slot retorne

            updateValidationState();

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Atualização inicial do estado de validação
        updateValidationState();
    });
</script>

</body>
</html>
