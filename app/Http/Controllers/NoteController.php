<?php

namespace App\Http\Controllers;

use App\Http\Resources\NoteColectionResource;
use App\Models\Note;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoteController extends Controller
{

    public function index()
    {
        $notes = Note::all();
        return $this->sendResponse(NoteColectionResource::collection($notes));
    }

    public function getPersonalNote()
    {
        auth()->user()->load('notes.user', 'notes.project');
        return $this->sendResponse(NoteColectionResource::collection(auth()->user()->notes));
    }

    public function getProjectNote(Project $project)
    {
        $project->load('notes.user', 'notes.project');
        return $this->sendResponse(NoteColectionResource::collection($project->notes));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $project = Project::find($request->project_id);
        if ($project && $project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $note = Note::create([
            'user_id' => Auth::id(),
            'project_id' => $validated['project_id'],
            'title' => $validated['title'],
            'content' => $validated['content'],
        ]);

        return $this->sendResponse(NoteColectionResource::make($note), 'Note created successfully!', 201);
    }

    public function show(Note $note)
    {
        return $this->sendResponse(NoteColectionResource::make($note));
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $note->load('project');
        if ($note->project_id !== null && $note->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $note->update($validated);
        return $this->sendResponse(NoteColectionResource::make($note));
    }

    public function destroy(Note $note)
    {
        $note->load('project');
        if ($note->project_id !== null && $note->project->is_completed) {
            return $this->sendError('Project already completed!', [], 403);
        }
        
        $this->authorize('delete', $note);
        $note->delete();
        return $this->sendResponse([], 'Note deleted successfully!');
    }
}
