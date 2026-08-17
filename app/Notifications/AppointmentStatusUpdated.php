<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AppointmentStatusUpdated extends Notification
{
    public function __construct(
        public Appointment $appointment,
        public string $previousStatus,
        public string $changedByRole // 'admin' | 'case_manager'
    ) {
        $this->appointment->loadMissing('client:id,name', 'caseManager:id,name');
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload());
    }

    public function broadcastType(): string
    {
        return 'appointment.status-updated';
    }

    protected function payload(): array
    {
        return [
            'notif_type'      => $this->appointment->status === 'cancelled' ? 'cancelled' : 'status_changed',
            'appointment_id'  => $this->appointment->id,
            'date'            => $this->appointment->date->format('Y-m-d'),
            'start_time'      => substr($this->appointment->start_time, 0, 5),
            'status'          => $this->appointment->status,
            'previous_status' => $this->previousStatus,
            'changed_by_role' => $this->changedByRole,
            'client'          => [
                'id'   => $this->appointment->client->id,
                'name' => $this->appointment->client->name,
            ],
            'case_manager'    => [
                'id'   => $this->appointment->caseManager->id,
                'name' => $this->appointment->caseManager->name,
            ],
        ];
    }
}