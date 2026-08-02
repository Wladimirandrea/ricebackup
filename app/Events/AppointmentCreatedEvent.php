<?php
// app/Events/AppointmentCreatedEvent.php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentCreatedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
        $this->appointment->loadMissing(
            'client:id,name',
            'caseManager:id,name'
        );
    }

    public function broadcastOn(): array
    {
        return [
            // Admin escucha todas las citas en el canal público
            new Channel('appointments'),

            // Case manager solo ve las citas de sus clientes
            new PrivateChannel('manager.' . $this->appointment->case_manager_id),

            // Cliente solo ve sus propias citas
            new PrivateChannel('client.' . $this->appointment->client_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->appointment->id,
            'date'         => $this->appointment->date->format('Y-m-d'),
            'start_time'   => substr($this->appointment->start_time, 0, 5),
            'end_time'     => substr($this->appointment->end_time,   0, 5),
            'status'       => $this->appointment->status,
            'notes'        => $this->appointment->notes,
            'client'       => [
                'id'   => $this->appointment->client->id,
                'name' => $this->appointment->client->name,
            ],
            'case_manager' => [
                'id'   => $this->appointment->caseManager->id,
                'name' => $this->appointment->caseManager->name,
            ],
        ];
    }
}