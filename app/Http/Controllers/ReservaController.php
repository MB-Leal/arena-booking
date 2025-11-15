<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Reserva;
use App\Http\Requests\UpdateReservaStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // 🛑 CRÍTICO: Importar Log
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;


class ReservaController extends Controller
{
    /**
     * Exibe a página pública de agendamento (que carrega os slots via API).
     */
    public function index()
    {
        return view('reserva.index');
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Checa se o horário de uma nova reserva entra em conflito com reservas existentes.
     * * @param string $date Data da reserva (Y-m-d)
     * @param string $startTime Hora de início (H:i:s)
     * @param string $endTime Hora de fim (H:i:s)
     * @param bool $isFixed Indica se a reserva que está sendo criada é um slot fixo (Admin)
     * @param ?int $ignoreReservaId ID da reserva a ignorar (útil para edição)
     * @return bool True se houver sobreposição.
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Query base para sobreposição de tempo (somente status que ocupam o slot)
        $baseQuery = Reserva::query()
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($isFixed) {
            // Lógica para criação/edição de slots FIXOS (Admin)

            // Um slot fixo não pode conflitar com outro slot fixo no mesmo dia da semana
            $conflitoComOutraFixa = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

            if ($conflitoComOutraFixa) { return true; }

            // Nem pode conflitar com uma reserva pontual/recorrente na data específica
            $conflitoPontualNaPrimeiraData = (clone $baseQuery)
                ->where('date', $date)
                ->where('is_fixed', false)
                ->exists();

            return $conflitoPontualNaPrimeiraData;

        } else {
            // Lógica para criação de RESERVA PONTUAL/RECORRENTE (Cliente/Admin)

            // Se tentamos criar uma pontual, ela não pode conflitar com outra reserva REAL (pontual/recorrente)
            // 🛑 CRÍTICO: Adiciona filtro para IGNORAR os SLOTS FIXOS (is_fixed=true)
            $conflitoNaDataExata = (clone $baseQuery)
                ->where('date', $date)
                ->where('is_fixed', false) // <--- CRÍTICO: Filtra para checar APENAS reservas de clientes
                ->exists();

            return $conflitoNaDataExata;
        }
    }

    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback (uso interno do Admin).
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA];

        $conflictingReservas = Reserva::whereIn('status', $activeStatuses)
            // 🛑 CRÍTICO: Filtra para checar APENAS reservas de clientes (pontuais/recorrentes)
            ->where('is_fixed', false)
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->pluck('id');

        return $conflictingReservas->implode(', ');
    }

    // =========================================================================
    // ✅ MÉTODO: Agendamento Rápido RECORRENTE via Calendário (API)
    // =========================================================================
    public function storeRecurrentReservaApi(Request $request)
    {
        // 1. Validação (Corrigido para G:i)
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_contact' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:G:i'],
            'end_time' => ['required', 'date_format:G:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reserva_id_to_update' => ['required', 'integer', 'exists:reservas,id'],
            'is_recurrent' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'];

        // 🛑 NORMALIZAÇÃO DA HORA ANTES DO USO
        $startTime = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTime = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $managerId = Auth::id();
        $reservaIdToUpdate = $validated['reserva_id_to_update'];
        $isRecurrentFlag = true;

        // 2. Checagem de Conflito para o primeiro slot (Pontual vs Tudo)
        $slotFixo = Reserva::where('id', $reservaIdToUpdate)
            ->where('is_fixed', true)
            ->where('date', $date)
            ->first();

        // Faz a checagem de sobreposição, ignorando a si mesmo (o slot fixo)
        // 🛑 Aqui o checkOverlap irá rodar com is_fixed=false, mas irá checar
        // contra is_fixed=false APENAS (graças à correção dentro do método).
        if (!$slotFixo || $this->checkOverlap($date, $startTime, $endTime, false, $reservaIdToUpdate)) {

            $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime, $reservaIdToUpdate);

             return response()->json([
                 'success' => false,
                 'message' => 'Conflito! O horário inicial não está mais disponível ou se sobrepõe a outra reserva. (IDs Conflitantes: ' . $conflictingIds . ') Recarregue a página.',
             ], 409);
        }

        // --- 2.5. CHECAGEM CRÍTICA DE PROTEÇÃO ANTI-SOBRESCRITA (Recorrente) ---
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;
        $endDateLimit = Carbon::today()->addYear()->toDateString();

        // Verifica se há reservas REAIS (is_fixed=0) já ocupando este slot futuro
        $conflitoFuturo = Reserva::where('day_of_week', $dayOfWeek)
            ->where('start_time', $startTime)
            ->where('end_time', $endTime)
            ->whereDate('date', '>', $date)
            ->whereDate('date', '<', $endDateLimit)
            ->where('is_fixed', false) // Checa apenas contra reservas REAIS
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->exists();

        if ($conflitoFuturo) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível criar uma reserva recorrente. Os horários futuros desta série já estão ocupados por outro cliente fixo ou exceções. Por favor, remova a opção Recorrente e agende apenas pontualmente.',
            ], 409);
        }
        // --- FIM DA CHECAGEM CRÍTICA ---


        // --- 3. CONVERTER TODA A SÉRIE RECORRENTE ---
        DB::beginTransaction();
        try {

            // 3.1. Converte o primeiro slot (clicado)
            $slotFixo->update([
                'user_id' => null,
                'manager_id' => $managerId,
                'schedule_id' => null,
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? 'Reserva Recorrente - Slot Inicial',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false, // O slot inicial VIRA a reserva pontual (real)
                'is_recurrent' => $isRecurrentFlag, // Marca como recorrente
                'recurrent_series_id' => null, // É a mestra

                // 🛑 CRÍTICO: Usa a hora normalizada
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            $masterReservaId = $slotFixo->id; // Captura o ID da mestra

            // 3.2. Localiza e BLOQUEIA os slots futuros correspondentes
            $futureFixedSlots = Reserva::where('is_fixed', true)
                ->where('day_of-week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->whereDate('date', '>', $date) // Apenas datas futuras
                ->whereDate('date', '<', $endDateLimit) // Até o limite de 1 ano de geração
                ->get();

            $countUpdated = 0;

            foreach ($futureFixedSlots as $futureSlot) {
                // Converte cada slot fixo em uma reserva confirmada para o cliente
                $futureSlot->update([
                    'user_id' => null,
                    'manager_id' => $managerId,
                    'schedule_id' => null,
                    'price' => $validated['price'],
                    'client_name' => $validated['client_name'],
                    'client_contact' => $validated['client_contact'],
                    'notes' => $validated['notes'] ?? 'Reserva Recorrente - Série',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => false,
                    'is_recurrent' => $isRecurrentFlag, // Marca como recorrente
                    'recurrent_series_id' => $masterReservaId, // Vincula à mestra
                ]);
                $countUpdated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Reserva Recorrente criada com sucesso! O slot inicial (ID {$masterReservaId}) foi agendado e mais {$countUpdated} slots futuros foram reservados e vinculados.",
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao converter slot fixo em reserva recorrente (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar a reserva recorrente. Detalhes no log.',
            ], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO: Agendamento Rápido Pontual (API)
    // =========================================================================
    public function storeQuickReservaApi(Request $request)
    {
        // 1. Validação (Corrigido para G:i)
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_contact' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:G:i'],
            'end_time' => ['required', 'date_format:G:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
            'schedule_id' => ['nullable'],
            // Campo do ID da Reserva Fixa a ser ATUALIZADA/CONVERTIDA
            'reserva_id_to_update' => ['required', 'integer', 'exists:reservas,id'],
        ]);

        $date = $validated['date'];

        // 🛑 NORMALIZAÇÃO DA HORA ANTES DO USO
        $startTime = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTime = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $managerId = Auth::id();
        $reservaIdToUpdate = $validated['reserva_id_to_update'];

        // 2. Checagem de Conflito (Pontual vs Tudo)
        $slotFixo = Reserva::where('id', $reservaIdToUpdate)
            ->where('is_fixed', true)
            ->where('date', $date)
            ->first();

        // 🛑 CRÍTICO: O checkOverlap deve ser feito APENAS contra reservas REAIS (is_fixed=false).
        if (!$slotFixo || $this->checkOverlap($date, $startTime, $endTime, false, $reservaIdToUpdate)) {

            $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime, $reservaIdToUpdate);

             return response()->json([
                 'success' => false,
                 'message' => 'Conflito! O horário não está mais disponível ou se sobrepõe a outra reserva. (IDs Conflitantes: ' . $conflictingIds . ') Recarregue a página.',
             ], 409);
        }

        // 3. Criação/Atualização da Reserva (Convertendo o Slot Fixo em Reserva de Cliente)
        DB::beginTransaction();
        try {
            // Atualiza o slot fixo existente com os dados do cliente, convertendo-o em uma reserva pontual
            $slotFixo->update([
                'user_id' => null,
                'manager_id' => $managerId,
                'schedule_id' => null,
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? 'Agendamento Rápido via Gestor',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false, // CRÍTICO: MARCA COMO RESERVA PONTUAL REAL!
                'is_recurrent' => false,
                'recurrent_series_id' => null,

                // 🛑 CRÍTICO: Usa a hora normalizada
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva rápida criada e confirmada com sucesso! O slot fixo foi convertido. O calendário será atualizado.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao converter slot fixo em reserva rápida (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar a reserva.',
            ], 500);
        }
    }
    // =========================================================================

    // =========================================================================
    // MÉTODO `storePublic` (COM DEBUGGING E CORREÇÃO DE HORA E CONFLITO)
    // =========================================================================
    /**
     * Salva a pré-reserva (Formulário Público).
     */
    public function storePublic(Request $request)
    {
        // 0. Pré-Sanitização do contato
        $contactValue = $request->input('contato_cliente', '');
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['contato_cliente' => $cleanedContact]);

        // 🛑 DEBUG CRÍTICO: Registra o input ANTES da falha de validação
        Log::info('[DEBUG STORE PUBLIC] Input Recebido:', $request->all());


        // 1. Definição manual das regras
        $rules = [
            'nome_cliente'      => ['required', 'string', 'max:255'],
            'contato_cliente'   => ['required', 'digits_between:10,11'],
            'data_reserva'      => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            // ✅ CORREÇÃO: Altera de H:i para G:i
            'hora_inicio'       => ['required', 'date_format:G:i'],
            'hora_fim'          => ['required', 'date_format:G:i', 'after:hora_inicio'],
            'price'             => ['required', 'numeric', 'min:0'],
            'schedule_id'       => ['required', 'integer'], // ID da Reserva Fixa para rastreamento
            'reserva_conflito_id' => 'nullable',
        ];

        // 2. Validação Manual com mensagens personalizadas
        $validator = Validator::make($request->all(), $rules, [
            'contato_cliente.digits_between' => 'O contato deve ter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'schedule_id.required' => 'O horário não foi selecionado corretamente. Tente selecionar o slot novamente no calendário.',
            'hora_inicio.date_format' => 'O horário de início deve estar no formato válido (H:i).',
            'hora_fim.date_format' => 'O horário de fim deve estar no formato válido (H:i).',
        ]);


        if ($validator->fails()) {
            // 🛑 DEBUG CRÍTICO: Registra os erros de validação
            Log::error('[DEBUG STORE PUBLIC] Erro de Validação:', $validator->errors()->toArray());

            // 🛑 CORREÇÃO: Redireciona para a rota 'reserva.index' que é a URL '/agendamento'
            return redirect()->route('reserva.index')->withErrors($validator)->withInput()->with('error', 'Correção Necessária! Por favor, verifique os campos destacados em vermelho.');
        }

        $validated = $validator->validated();

        // 🛑 DEBUG CRÍTICO: Registra o input VALIDADO (antes do conflito)
        Log::info('[DEBUG STORE PUBLIC] Input Validado:', $validated);


        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $price = $validated['price'];
        $scheduleId = $validated['schedule_id'];

        // 🛑 NORMALIZAÇÃO DA HORA: Converte de G:i (ex: 6:00) para H:i:s (ex: 06:00:00)
        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');


        // === 3. Checagem de Conflito FINAL (CRÍTICO) ===
        // Verifica se o slot não foi ocupado por outro cliente segundos antes
        // 🛑 O checkOverlap agora filtra corretamente, ignorando o slot fixo que o cliente clicou.
        if ($this->checkOverlap($date, $startTimeNormalized, $endTimeNormalized, false)) {
            // 🛑 DEBUG CRÍTICO: Registra que houve um conflito
            Log::warning('[DEBUG STORE PUBLIC] CONFLITO DETECTADO no momento da submissão.', [
                'date' => $date,
                'start_time' => $startTime
            ]);

            $validator->errors()->add('reserva_conflito_id', 'ERRO: Este horário acabou de ser reservado ou está em conflito. Tente selecionar outro.');
            throw new ValidationException($validator);
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // 🛑 CRÍTICO: Criamos a nova reserva PONTUAL PENDENTE do cliente.
        $reserva = Reserva::create([
            'date' => $date,
            'start_time' => $startTimeNormalized, // 🛑 USANDO HORA NORMALIZADA
            'end_time' => $endTimeNormalized,     // 🛑 USANDO HORA NORMALIZADA
            'client_name' => $validated['nome_cliente'],
            'client_contact' => $request->input('contato_cliente'),
            'price' => $price,
            // 🛑 CRÍTICO: Atribui o ID do slot fixo original para rastreamento
            'schedule_id' => $scheduleId,
            'status' => Reserva::STATUS_PENDENTE,
            'is_fixed' => false, // É uma reserva real
            'day_of_week' => $dayOfWeek,
        ]);

        $whatsappNumber = '91985320997'; // Altere para o seu número WhatsApp
        $data = Carbon::parse($reserva->date)->format('d/m/Y');
        $hora = Carbon::parse($reserva->start_time)->format('H:i');

        $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
            "Cliente: {$reserva->client_name}\n" .
            "Contato: {$reserva->client_contact}\n" .
            "Data/Hora: {$data} às {$hora}\n" .
            "Valor: R$ " . number_format($reserva->price, 2, ',', '.') . "\n" .
            "Tipo: RESERVA PONTUAL\n";

        $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);

        // 🛑 Redireciona para a rota 'reserva.index' com a mensagem de sucesso e o link do WhatsApp.
        return redirect()->route('reserva.index')
            ->with('whatsapp_link', $whatsappLink)
            ->with('success', 'Pré-reserva enviada! Por favor, entre em contato via WhatsApp para confirmar o agendamento.');
    }
    // =========================================================================


    // =========================================================================
    // MÉTODO `countPending` (API para Dashboard)
    // =========================================================================
    /**
     * Retorna a contagem de reservas com status 'pendente' (hoje ou no futuro E AINDA NÃO EXPIRADAS).
     */
    public function countPending()
    {
        $now = Carbon::now();
        $todayString = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        $futureOrTodayCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->whereDate('date', '>=', $todayString)
            ->where(function ($query) use ($todayString, $nowTime) {
                $query->whereDate('date', '>', $todayString)
                    ->orWhere(function ($q) use ($todayString, $nowTime) {
                        $q->whereDate('date', $todayString)
                          ->where('end_time', '>', $nowTime);
                    });
            })
            ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
    // =========================================================================
}
