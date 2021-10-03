<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\LessonRequest;
use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;

class LessonController extends Controller
{

    public function index()
    {
        return Inertia::render('Admin/Lessons/Index', ['lessons' => Lesson::all()]);
    }

    public function create()
    {
        return Inertia::render('Admin/Lessons/Create');
    }

    public function store(LessonRequest $request)
    {
        $this->handleLesson(new Lesson(), $request);

        return redirect()->route('cours.index')->with('success', 'Cours enregistré avec succès.');
    }

    public function show(Lesson $lesson)
    {
        //
    }

    public function edit(Lesson $cour)
    {
        return Inertia::render('Admin/Lessons/Edit', ['lesson' => $cour]);
    }

    public function update(LessonRequest $request, Lesson $cour)
    {
        $this->handleLesson($cour, $request, true);
        return redirect()->route('cours.index')->with('success', 'Cours mit à jour avec succès.');
    }

    public function destroy(Lesson $cour)
    {
        if (request()->user()->hasRole('administrator')) {
            $cour->delete();
        }
    }

    private function handleLesson(Lesson $lesson, LessonRequest $request, bool $update = false)
    {
        $function = $update ? 'update' : 'create';
        $fields = array_merge(
            $request->validated(),
            ['year' => now()->year . ' - ' . now()->addYear()->year]
        );

        $lesson->$function($fields);

        /* TODO: change code by this for PHP v8.1
         ...$request->validated(),
        'year' => now()->year . '-' . now()->addYear()->year,
        */
    }
}
