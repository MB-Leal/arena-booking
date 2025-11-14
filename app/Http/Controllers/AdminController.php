<?php
// [START OF FILE]

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
// ❌ REMOVIDO: use App\Models\Schedule;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

// --- IMPORTS ---
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        // Esta linha continua calculando a contagem de pendências
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        // O método retorna APENAS a contagem de pendências. O calendário carrega os eventos via API.
        return view('dashboard', compact('reservasPendentesCount'));
    }

    // =========================================================================
    // 🗓️ MÉTODO API: RESERVAS CONFIRMADAS PARA FULLCALENDAR (ADAPTADO)
    // =========================================================================
    /**
     * Retorna as reservas CONFIRMADAS/PENDENTES REAIS (is_fixed = false) em formato JSON para o FullCalendar.
     */
    public function getConfirmedReservasApi(Request $request)
    {
        // O FullCalendar envia os parâmetros 'start' e 'end' para filtrar o período
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();

        // 🛑 CRÍTICO: Busca APENAS reservas REAIS de clientes (is_fixed = false) para o calendário.
        $reservas = Reserva::where('is_fixed', false)
                            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                            ->whereDate('date', '>=', $start->toDateString())
                            ->whereDate('date', '<=', $end->toDateString())
                            ->with('user')
                            ->get();

        $events = $reservas->map(function ($reserva) {
            $bookingDate = $reserva->date->toDateString();

            // Usa os campos de TIME para construir o DateTime
            $start = Carbon::parse($bookingDate . ' ' . $reserva->start_time);
            $end = $reserva->end_time ? Carbon::parse($bookingDate . ' ' . $reserva->end_time) : $start->copy()->addHour();

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';
            $statusColor = $reserva->status === Reserva::STATUS_PENDENTE ? '#ff9800' : '#4f46e5'; // Laranja/Indigo
            $statusText = $reserva->status === Reserva::STATUS_PENDENTE ? 'PENDENTE: ' : 'RESERVADO: ';

            // Monta o título do evento
            $title = $statusText . $clientName;
            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            return [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'color' => $statusColor,
                'className' => 'fc-event-booked',
                'extendedProps' => [
                    'status' => $reserva->status,
                    'client_contact' => $reserva->client_contact,
                ]
            ];
        });

        return response()->json($events);
    }
    // =========================================================================

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

    /**
     * Exibe o índice de reservas confirmadas, ordenadas por data crescente.
     */
    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            // 🛑 CRÍTICO: Exclui reservas fixas de clientes reais (se houver a série recorrente antiga)
                            // O foco aqui é gerenciar agendamentos PONTUAIS confirmados e a grade (que agora está no /config).
                            ->where('is_fixed', false)
                            ->whereDate('date', '>=', Carbon::today()->toDateString())
                            ->with('user');

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
            $query->where('manager_id', Auth::id());
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas (Próximos Agendamentos)';
        }

        $reservas = $query->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user');
        return view('admin.reservas.show', compact('reserva'));
    }

    // =========================================================================
    // ✅ MÉTODO RECUPERADO: createReserva (Resolve o erro 500 da rota)
    // =========================================================================
    /**
     * Redireciona a rota de criação manual para o Dashboard,
     * incentivando o uso do agendamento rápido via calendário.
     *
     * MANTÉM a rota admin.reservas.create funcionando, mas direciona o gestor para o fluxo moderno.
     */
    public function createReserva()
    {
        return redirect()->route('dashboard')
            ->with('warning', 'A criação manual foi simplificada! Por favor, use o calendário (slots verdes) na tela principal para agendamento rápido.');
    }
    // =========================================================================

    // ❌ REMOVIDO: public function storeReserva(Request $request) { ... }
    // A criação manual é feita pelo FullCalendar API.

    // ❌ REMOVIDO: public function makeRecurrent(Request $request) { ... }
    // Foi substituída pela lógica de geração no ConfigurationController.


    // --- MÉTODOS DE AÇÕES (Mantidos, mas garantindo que o checkOverlap está no ReservaController) ---

    public function confirmarReserva(Reserva $reserva)
    {
        // Garante que o método checkOverlap é chamado a partir do ReservaController (agora público)
        $reservaController = app(\App\Http\Controllers\ReservaController::class);

        try {
            $dateString = $reserva->date->toDateString();
            $isFixed = $reserva->is_fixed;
            $ignoreId = $reserva->id;

            // 1. Checagem de Conflito (Usando ReservaController)
            if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                 return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento (Pendente ou Confirmado) no mesmo horário.');
            }

            // 2. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_CONFIRMADA,
                'manager_id' => Auth::id(), // O gestor que confirma
            ]);

            return redirect()->route('dashboard')
                             ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    // ... (MANTIDOS: rejeitarReserva, cancelarReserva, updateStatusReserva, destroyReserva) ...

    public final function rejeitarReserva(Reserva $reserva)
    {
        try {
            $reserva->update([
                'status' => Reserva::STATUS_REJEITADA,
                'manager_id' => Auth::id(),
            ]);
            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');
        } catch (\Exception $e) {
            Log::error("Erro ao rejeitar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    public function cancelarReserva(Reserva $reserva)
    {
        try {
            $reserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'manager_id' => Auth::id(),
            ]);
            return redirect()->route('admin.reservas.confirmed_index')
                                 ->with('success', 'Reserva cancelada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao cancelar a reserva ID {$reserva->id}: " . $e->getMessage());
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
        $updateData = ['status' => $newStatus];

        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            $reservaController = app(\App\Http\Controllers\ReservaController::class);
            try {
                $dateString = $reserva->date->toDateString();
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                     return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento (Pendente ou Confirmado) neste horário.');
                }
                $updateData['manager_id'] = Auth::id();
            } catch (\Exception $e) {
                 return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        if (in_array($newStatus, [Reserva::STATUS_REJEITADA, Reserva::STATUS_CANCELADA]) && !isset($updateData['manager_id'])) {
            $updateData['manager_id'] = Auth::id();
        }

        try {
            $reserva->update($updateData);
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

    // --- Métodos de CRUD de Usuários (Mantidos) ---

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
