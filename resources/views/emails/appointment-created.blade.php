<!-- resources/views/emails/appointment-created.blade.php -->
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isEs ? 'Nueva Cita' : 'New Appointment' }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f0f4f8;
            margin: 0; padding: 0;
        }
        .wrapper {
            max-width: 560px;
            margin: 32px auto;
            background: #ffffff;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .header {
            background: #1565c0;
            padding: 32px 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 1.3rem;
            margin: 0;
            font-weight: 700;
        }
        .header p {
            color: rgba(255,255,255,0.78);
            margin: 8px 0 0;
            font-size: 0.88rem;
        }
        .body { padding: 28px 32px; }
        .greeting {
            font-size: 1rem;
            color: #1a2a4a;
            margin: 0 0 8px;
            font-weight: 600;
        }
        .subtitle {
            color: #475569;
            font-size: 0.9rem;
            margin: 0 0 24px;
        }
        .card {
            background: #f0f4f8;
            border-radius: 14px;
            padding: 4px 20px;
            margin-bottom: 20px;
        }
        /* ✅ Table en lugar de flex — compatible con todos los clientes de correo */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-table tr {
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-table tr:last-child {
            border-bottom: none;
        }
        .detail-table td {
            padding: 11px 4px;
            font-size: 0.88rem;
            vertical-align: middle;
        }
        .detail-table .lbl {
            color: #64748b;
            font-weight: 600;
            width: 40%;
        }
        .detail-table .val {
            color: #1a2a4a;
            font-weight: 700;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .status-pending   { background: #fef3c7; color: #d97706; }
        .status-confirmed { background: #dbeafe; color: #1d4ed8; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .note {
            color: #94a3b8;
            font-size: 0.82rem;
            margin: 0 0 8px;
        }
        .footer {
            background: #f8fafc;
            padding: 18px 32px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p { color: #94a3b8; font-size: 0.78rem; margin: 0; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <h1>📅 {{ $isEs ? 'Nueva Cita Agendada' : 'New Appointment Scheduled' }}</h1>
        <p>{{ $isEs
            ? 'Se ha registrado una nueva cita en el sistema.'
            : 'A new appointment has been registered in the system.' }}</p>
    </div>

    {{-- Body --}}
    <div class="body">

        <p class="greeting">
            @if($recipientType === 'client')
                {{ $isEs ? 'Hola, ' : 'Hello, ' }}{{ $appointment->client->name }}!
            @else
                {{ $isEs ? 'Hola, ' : 'Hello, ' }}{{ $appointment->caseManager->name }}!
            @endif
        </p>

        <p class="subtitle">
            @if($recipientType === 'client')
                {{ $isEs
                    ? 'Se ha agendado una nueva cita para ti.'
                    : 'A new appointment has been scheduled for you.' }}
            @else
                {{ $isEs
                    ? 'Tienes una nueva cita agendada con un cliente.'
                    : 'You have a new appointment scheduled with a client.' }}
            @endif
        </p>

        {{-- Detalles --}}
        <div class="card">
            <table class="detail-table">
                <tr>
                    <td class="lbl">{{ $isEs ? 'Fecha' : 'Date' }}</td>
                    <td class="val">
                        @if($isEs)
                            {{ \Carbon\Carbon::parse($appointment->date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        @else
                            {{ \Carbon\Carbon::parse($appointment->date)->format('l, F j, Y') }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $isEs ? 'Hora' : 'Time' }}</td>
                    <td class="val">
                        {{ substr($appointment->start_time, 0, 5) }} — {{ substr($appointment->end_time, 0, 5) }}
                    </td>
                </tr>
                <tr>
                    <td class="lbl">{{ $isEs ? 'Cliente' : 'Client' }}</td>
                    <td class="val">{{ $appointment->client->name }}</td>
                </tr>
                <tr>
                    <td class="lbl">Case Manager</td>
                    <td class="val">{{ $appointment->caseManager->name }}</td>
                </tr>
                @if($appointment->notes)
                <tr>
                    <td class="lbl">{{ $isEs ? 'Notas' : 'Notes' }}</td>
                    <td class="val">{{ $appointment->notes }}</td>
                </tr>
                @endif
                <tr>
                    <td class="lbl">Status</td>
                    <td class="val">
                        <span class="status-badge status-{{ $appointment->status }}">
                            {{ ucfirst($appointment->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        <p class="note">
            {{ $isEs
                ? 'Si tienes alguna pregunta, contacta a tu administrador.'
                : 'If you have any questions, please contact your administrator.' }}
        </p>

    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>RAISE — {{ $isEs ? 'Sistema de Gestión de Citas' : 'Appointment Management System' }}</p>
    </div>

</div>
</body>
</html>