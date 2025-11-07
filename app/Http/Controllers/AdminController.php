<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        // 1. Buscar todas as reservas confirmadas
        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->with('user')
                            ->get()
                            ->filter();

        // 2. Formatar as reservas para o FullCalendar
        $events = [];
        foreach ($reservas as $reserva) {

            // 💡 CORRIGIDO: Usa $reserva->date diretamente
            $bookingDate = $reserva->date->toDateString();
            $startDateTimeString = $bookingDate . ' ' . $reserva->start_time;
            $start = Carbon::parse($startDateTimeString);

            if ($reserva->end_time) {
                $endDateTimeString = $bookingDate . ' ' . $reserva->end_time;
                $end = Carbon::parse($endDateTimeString);
            } else {
                $end = $start->copy()->addHour();
            }

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';
            $title = 'Reservado: ' . $clientName;

            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            $events[] = [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#10B981',
                'borderColor' => '#059669',
            ];
        }

        $eventsJson = json_encode($events);
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        return view('dashboard', compact('eventsJson', 'reservasPendentesCount'));
    }

    // --- Métodos de Listagem, Ação e Status de Reservas ---

    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        $pageTitle = 'Pré-Reservas Pendentes';

        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->with('user');

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $query->where('manager_id', auth()->id());
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas';
        }
        // 💡 CORRIGIDO: Ordenação pela coluna 'date'
        $reservas = $query->orderBy('date', 'desc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user');
        return view('admin.reservas.show', compact('reserva'));
    }

    public function confirmarReserva(Reserva $reserva)
    {
        try {
            // 1. Verificação de Conflito
            $dateString = $reserva->date->toDateString(); // 💡 CORRIGIDO: Usando 'date'
            $start_time_carbon = Carbon::parse($dateString . ' ' . $reserva->start_time);
            $end_time_carbon = Carbon::parse($dateString . ' ' . $reserva->end_time);

            $isConflict = Reserva::where('id', '!=', $reserva->id)
                                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA])
                                        ->where('date', $dateString) // 💡 CORRIGIDO: Usando 'date'
                                        ->where(function ($q) use ($start_time_carbon, $end_time_carbon) {
                                            $q->where('start_time', '<', $end_time_carbon->toTimeString())
                                                ->where('end_time', '>', $start_time_carbon->toTimeString());
                                        })->exists();

            if ($isConflict) {
                return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento CONFIRMADO no mesmo horário.');
            }

            // 2. Confirma a reserva
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->save();

            return redirect()->route('dashboard')
                                 ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    public final function rejeitarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = Reserva::STATUS_REJEITADA;
            $reserva->save();

            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    public function cancelarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->save();

            return redirect()->route('admin.reservas.confirmed_index')
                                 ->with('success', 'Reserva cancelada com sucesso.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar a reserva: ' . $e->getMessage());
        }
    }

    public function updateStatusReserva(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_REJEITADA,
                Reserva::STATUS_CANCELADA,
            ])],
        ]);

        $newStatus = $validated['status'];

        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            try {
                $dateString = $reserva->date->toDateString(); // 💡 CORRIGIDO: Usando 'date'
                $start_time_carbon = Carbon::parse($dateString . ' ' . $reserva->start_time);
                $end_time_carbon = Carbon::parse($dateString . ' ' . $reserva->end_time);

                $isConflict = Reserva::where('id', '!=', $reserva->id)
                                        ->where('status', Reserva::STATUS_CONFIRMADA)
                                        ->where('date', $dateString) // 💡 CORRIGIDO: Usando 'date'
                                        ->where(function ($q) use ($start_time_carbon, $end_time_carbon) {
                                            $q->where('start_time', '<', $end_time_carbon->toTimeString())
                                                ->where('end_time', '>', $start_time_carbon->toTimeString());
                                        })->exists();

                if ($isConflict) {
                    return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento CONFIRMADO neste horário.');
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        try {
            $reserva->status = $newStatus;
            $reserva->manager_id = auth()->id();
            $reserva->save();

            return redirect()->route('admin.reservas.show', $reserva)
                             ->with('success', "Status da reserva alterado para '{$newStatus}' com sucesso.");

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da reserva: ' . $e->getMessage());
        }
    }

    public function destroyReserva(Reserva $reserva)
    {
        try {
            $reserva->delete();

            return redirect()->route('admin.reservas.index')
                             ->with('success', 'Reserva excluída permanentemente com sucesso.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir a reserva: ' . $e->getMessage());
        }
    }

    public function makeRecurrent(Request $request)
    {
        // 1. Validação dos Dados
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $dayOfWeek = $startDate->dayOfWeek;

        $recurrentSeriesId = now()->timestamp . $validated['user_id'];
        $reservasCriadas = 0;
        $conflitos = 0;

        // 2. Loop Semanal para Gerar as Reservas
        $currentDate = $startDate->copy();

        while ($currentDate->lessThanOrEqualTo($endDate)) {

            if ($currentDate->dayOfWeek === $dayOfWeek) {

                // 3. Verificação de Conflito
                $conflitoExistente = Reserva::where('date', $currentDate->toDateString()) // 💡 CORRIGIDO: Usando 'date'
                    ->where('status', Reserva::STATUS_CONFIRMADA)
                    ->where(function ($query) use ($validated) {
                        $query->where('start_time', '<', $validated['end_time'])
                                ->where('end_time', '>', $validated['start_time']);
                    })
                    ->exists();

                if (!$conflitoExistente) {
                    // 4. Criação da Reserva Recorrente
                    Reserva::create([
                        'user_id' => $validated['user_id'],
                        'schedule_id' => null,
                        'date' => $currentDate->toDateString(), // 💡 CORRIGIDO: Usando 'date'
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'price' => $validated['price'],
                        'client_name' => User::find($validated['user_id'])->name ?? 'Cliente Fixo',
                        'client_contact' => 'Recorrente',
                        'notes' => $validated['notes'],
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'recurrent_series_id' => $recurrentSeriesId,
                        'is_fixed' => true,
                        'day_of_week' => $dayOfWeek,
                    ]);
                    $reservasCriadas++;
                } else {
                    $conflitos++;
                }
            }
            $currentDate->addWeek();
        }

        // 5. Retorno ao Gestor
        $message = "Série de horários fixos criada. Total de reservas geradas: {$reservasCriadas}.";
        if ($conflitos > 0) {
            $message .= " Atenção: {$conflitos} datas foram puladas devido a conflitos de horário.";
        }

        return redirect()->route('admin.reservas.confirmed_index')->with('success', $message);
    }

    // =================================================================
    // MÉTODOS DE CRIAÇÃO MANUAL DE RESERVA (GESTOR)
    // =================================================================

    public function createReserva()
    {
        // 1. DADOS DE DISPONIBILIDADE RECORRENTE (Schedule - Reservas Fixas)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                            ->select('day_of_week', 'start_time', 'end_time')
                                            ->get();

        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        $availableRecurringSchedules = Horario::whereNotNull('day_of_week')
                                             ->whereNull('date')
                                             ->where('is_active', true)
                                             ->get()
                                             ->filter(function ($schedule) use ($fixedReservaMap) {
                                                $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                                return !in_array($scheduleKey, $fixedReservaMap);
                                             });

        $availableDayOfWeeks = $availableRecurringSchedules->pluck('day_of_week')->unique()->map(fn($day) => (int)$day)->toArray();

        // 2. DADOS DE DISPONIBILIDADE AVULSA (Schedule.date)
        $hoje = Carbon::today();
        $diasParaVerificar = 180;

        $adHocDates = Horario::whereNotNull('date')
                             ->where('is_active', true)
                             ->where('date', '>=', $hoje->toDateString())
                             ->where('date', '<=', $hoje->copy()->addDays($diasParaVerificar)->toDateString())
                             ->pluck('date')
                             ->map(fn($date) => $date->toDateString())
                             ->unique()
                             ->toArray();

        // 3. COMBINAÇÃO E PROJEÇÃO NO TEMPO
        $diasDisponiveisNoFuturo = [];
        $period = CarbonPeriod::create($hoje, $hoje->copy()->addDays($diasParaVerificar));

        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek;

            $isRecurringAvailable = in_array($dayOfWeek, $availableDayOfWeeks);
            $isAdHocAvailable = in_array($currentDateString, $adHocDates);

            if ($isRecurringAvailable || $isAdHocAvailable) {
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }

        // 4. RETORNO PARA A VIEW
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    public function storeReserva(Request $request)
    {
        $data = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            // 💡 CORREÇÃO CRÍTICA: Altera 'horarios' para 'schedules'
            'schedule_id' => 'required|exists:schedules,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'date.after_or_equal' => 'A data da reserva deve ser hoje ou uma data futura.',
            'end_time.after' => 'A hora de fim deve ser depois da hora de início.',
        ]);

        $date = $data['date'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // 2. VERIFICAÇÃO CRUCIAL DE CONFLITO (Confirmadas e Pendentes)
        $overlap = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where('date', $date) // 💡 CORRIGIDO: Usando 'date'
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'O horário selecionado já está reservado (confirmado) ou em pré-reserva (pendente) para esta data. Por favor, escolha outro slot.');
        }

        // 3. CRIAÇÃO E CONFIRMAÇÃO IMEDIATA
        Reserva::create([
            'client_name' => $data['client_name'],
            'client_contact' => $data['client_contact'],
            'price' => $data['price'],
            'notes' => $data['notes'] ?? 'Reserva criada manualmente pelo gestor.',
            'schedule_id' => $data['schedule_id'],
            'date' => $date, // 💡 CORRIGIDO: Usando 'date'
            'start_time' => $startTime,
            'end_time' => $endTime,
            'manager_id' => auth()->id(),
            'user_id' => null,
            'status' => Reserva::STATUS_CONFIRMADA,
            'is_fixed' => false,
            'day_of_week' => Carbon::parse($date)->dayOfWeek,
            'recurrent_series_id' => null,
        ]);

        return redirect()->route('admin.reservas.confirmed_index')->with('success', 'Reserva manual criada e confirmada com sucesso para ' . $data['client_name'] . '!');
    }

    /**
     * Calcula e retorna os horários disponíveis para uma data específica.
     */
    public function getAvailableTimes(Request $request)
    {
        // 1. Validação da Data
        $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
        ]);

        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $dayOfWeek = $selectedDate->dayOfWeek; // 0=Dom a 6=Sáb

        // Se a data for hoje, precisamos checar os horários que já passaram
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        // A. Slots Fixos Ocupados por Reservas Fixas (Chave de Exclusão Recorrente)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                   ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                   ->select('day_of_week', 'start_time', 'end_time')
                                   ->get();
        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        // B. Slots Definidos pelo Admin (Schedule) para esta data

        // 1. Slots Recorrentes (Filtrados)
        $recurringSchedules = Horario::whereNotNull('day_of_week')
                                    ->whereNull('date')
                                    ->where('is_active', true)
                                    ->where('day_of_week', $dayOfWeek)
                                    ->get()
                                    ->filter(function ($schedule) use ($fixedReservaMap) {
                                        $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                        return !in_array($scheduleKey, $fixedReservaMap);
                                    });

        // 2. Slots Avulsos (Específicos da Data)
        $adHocSchedules = Horario::whereNotNull('date')
                                 ->where('is_active', true)
                                 ->where('date', $dateString)
                                 ->get();

        // 3. Combina e ordena os horários disponíveis definidos
        $allSchedules = $recurringSchedules->merge($adHocSchedules)->sortBy('start_time');


        // C. Slots Ocupados por Reservas Pontuais (Chave de Exclusão Pontual)
        $existingReservations = Reserva::where('is_fixed', false)
                                     ->whereDate('date', $dateString) // 💡 CORRIGIDO: Usando 'date'
                                     ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                     ->get();

        // D. Filtra os horários disponíveis finais
        $availableTimes = $allSchedules->filter(function ($schedule) use ($existingReservations, $isToday, $now, $selectedDate) {

            // 1. Checagem de slots passados (apenas se for hoje)
            $scheduleStartDateTime = Carbon::parse($selectedDate->toDateString() . ' ' . $schedule->start_time);

            if ($isToday && $scheduleStartDateTime->lt($now)) {
                return false;
            }

            // 2. Checagem de Conflito com Reservas Pontuais (occupied)
            $isBooked = $existingReservations->contains(function ($reservation) use ($schedule) {
                // Checa se há sobreposição de horário
                return $reservation->start_time < $schedule->end_time && $reservation->end_time > $schedule->start_time;
            });

            return !$isBooked;
        })->map(function ($schedule) {
            // Formata os dados para o JavaScript
            return [
                'id' => $schedule->id,
                'time_slot' => Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . Carbon::parse($schedule->end_time)->format('H:i'),
                'price' => number_format($schedule->price, 2, ',', '.'),
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'raw_price' => $schedule->price,
                'schedule_id' => $schedule->id,
            ];
        })->values();

        return response()->json($availableTimes);
    }

    // --- Métodos de CRUD de Usuários ---

    public function indexUsers()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor'])],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }
}
