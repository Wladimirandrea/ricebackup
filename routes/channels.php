<?php
// routes/channels.php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado del case manager — solo el propio manager puede suscribirse
Broadcast::channel('manager.{managerId}', function ($user, $managerId) {
    return (int) $user->id === (int) $managerId && $user->role === 'case_manager';
});

// Canal privado del cliente — solo el propio cliente puede suscribirse
Broadcast::channel('client.{clientId}', function ($user, $clientId) {
    return (int) $user->id === (int) $clientId && $user->role === 'client';
});