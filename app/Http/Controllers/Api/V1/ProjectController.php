<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Get all user projects
     *
     * Retrieves a list of all projects belonging to the authenticated user,
     * ordered by most recently created first.
     *
     */
    public function index(Request $request)
    {
        return $request->user()->projects()->latest()->get();
    }

    /**
     * Create a new project
     *
     * Creates a new project for the authenticated user with the provided data.
     * Title is required, code is optional and can be an array.
     *
     * @param Request $request Contains project data and authenticated user
     * @return JsonResponse Returns the created project with HTTP 201 status
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'code'  => 'nullable|array',
        ]);

        $project = $request->user()->projects()->create($data);

        return response()->json($project, 201);
    }

    /**
     * Get specific project
     *
     * Retrieves details of a specific project. User must be the owner of the project.
     *
     * @param Project $project The project to retrieve
     * @param Request $request Contains authenticated user information
     * @return JsonResponse Returns the requested project data
     * @throws AuthorizationException If user is not the project owner
     */
    public function show(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProjectOwner($project, $request->user());
        return response()->json($project);
    }

    /**
     * Update project
     *
     * Updates an existing project. User must be the owner of the project.
     * Title can be updated and must be provided if included. Code is optional.
     *
     * @param Request $request Contains updated project data
     * @param Project $project The project to update
     * @return JsonResponse Returns the updated project data
     * @throws AuthorizationException If user is not the project owner
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectOwner($project, $request->user());

        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'code'  => 'nullable|array',
        ]);

        $project->update($data);
        return response()->json($project);
    }

    /**
     * Delete project
     *
     * Permanently deletes a project. User must be the owner of the project.
     *
     * @param Request $request Contains authenticated user information
     * @param Project $project The project to delete
     * @return JsonResponse Returns empty response with HTTP 204 status
     * @throws AuthorizationException If user is not the project owner
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectOwner($project, $request->user());
        $project->delete();
        return response()->json(null, 204);
    }
    
    /**
     * Authorize project owner
     *
     * Helper method to check if the authenticated user is the owner of the project.
     * Throws a 403 Forbidden exception if user is not the owner.
     *
     * @param Project $project The project to check ownership of
     * @param User $user The user to validate ownership against
     * @throws AuthorizationException If user is not the project owner
     */
    protected function authorizeProjectOwner(Project $project, $user)
    {
        if ($project->user_id !== $user->id) {
            abort(403, 'Not authorized');
        }
    }
}
