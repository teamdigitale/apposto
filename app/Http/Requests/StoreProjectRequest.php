<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        if ($project instanceof Project) {
            return $this->user()?->can('update', $project) ?? false;
        }

        return $this->user()?->can('create', Project::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'active' => 'boolean',
            'slack_channel' => 'nullable|url|max:500',
            'drive_folder' => 'nullable|url|max:500',
            'documentation_url' => 'nullable|url|max:500',
            'resources_notes' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'active' => $this->boolean('active', true),
        ]);
    }
}
