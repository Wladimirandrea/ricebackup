<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class ManagerTaskController extends Controller
{
    // GET /manager/tasks
    public function index(Request $request)
    {
        $tasks = Task::where('case_manager_id', $request->user()->id)
            ->orderBy('completed')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $tasks]);
    }

    // POST /manager/tasks
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:baja,media,alta'],
        ]);

        $task = Task::create([
            'case_manager_id' => $request->user()->id,
            'title' => $validated['title'],
            'priority' => $validated['priority'],
            'completed' => false,
        ]);

        return response()->json(['task' => $task], 201);
    }

    // PATCH /manager/tasks/{task}
    public function update(Request $request, Task $task)
    {
        abort_if($task->case_manager_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'priority' => ['sometimes', 'in:baja,media,alta'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $task->update($validated);

        return response()->json(['task' => $task]);
    }

    // DELETE /manager/tasks/{task}
    public function destroy(Request $request, Task $task)
    {
        abort_if($task->case_manager_id !== $request->user()->id, 403);

        $task->delete();

        return response()->json(['message' => 'Tarea eliminada']);
    }
}