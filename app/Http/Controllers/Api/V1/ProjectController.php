<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->projects()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code'  => 'nullable|array',
        ]);

        $project = $request->user()->projects()->create($data);

        return response()->json($project, 201);
    }

    public function show(Project $project, Request $request)
    {
        $this->authorizeProjectOwner($project, $request->user());
        return $project;
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProjectOwner($project, $request->user());

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'code'  => 'nullable|array',
        ]);

        $project->update($data);
        return response()->json($project);
    }

    public function destroy(Request $request, Project $project)
    {
        $this->authorizeProjectOwner($project, $request->user());
        $project->delete();
        return response()->json(null, 204);
    }

    protected function authorizeProjectOwner(Project $project, $user)
    {
        if ($project->user_id !== $user->id) {
            abort(403, 'Not authorized');
        }
    }
}
