<?php
namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    // List all templates
    public function index()
    {
        return Template::with('templateDays')->get();
    }

    // Show a single template
    public function show($id)
    {
        return Template::with('templateDays')->findOrFail($id);
    }

    // Create a new template
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_days' => 'required|array',
            'template_days.*.day_of_week' => 'required|integer|between:1,7',
            'template_days.*.shift_data' => 'required|array',
        ]);

        $template = Template::create([
            'name' => $validated['name'],
            'created_by' => $request->user()->id, // Assuming authenticated user
        ]);

        foreach ($validated['template_days'] as $day) {
            $template->templateDays()->create([
                'day_of_week' => $day['day_of_week'],
                'shift_data' => $day['shift_data'],
            ]);
        }

        return response()->json($template->load('templateDays'), 201);
    }

    // Update a template
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'template_days' => 'sometimes|array',
            'template_days.*.day_of_week' => 'required_with:template_days|integer|between:1,7',
            'template_days.*.shift_data' => 'required_with:template_days|array',
        ]);

        $template->update($validated);

        if (isset($validated['template_days'])) {
            $template->templateDays()->delete(); // Clear old data
            foreach ($validated['template_days'] as $day) {
                $template->templateDays()->create([
                    'day_of_week' => $day['day_of_week'],
                    'shift_data' => $day['shift_data'],
                ]);
            }
        }

        return response()->json($template->load('templateDays'));
    }

    // Delete a template
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();

        return response()->json(['message' => 'Template deleted'], 200);
    }
}
