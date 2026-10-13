<?php

namespace Database\Seeders;

use App\Models\DietPlan;
use App\Models\Exercise;
use App\Models\WorkoutCategory;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanExercise;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FitnessDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->importWorkouts();
            $this->importDiets();
        });
    }

    private function importWorkouts(): void
    {
        $data = json_decode(file_get_contents(public_path('data/workouts.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($data['categories'] as $categoryOrder => $dataCategory) {
            $category = WorkoutCategory::firstOrCreate(['slug' => $dataCategory['id']], [
                'title' => $dataCategory['title'], 'icon' => $dataCategory['icon'],
                'description' => $dataCategory['description'], 'sort_order' => $categoryOrder,
            ]);
            $plan = WorkoutPlan::withTrashed()->firstOrCreate(['slug' => $dataCategory['id']], [
                'title' => $dataCategory['title'], 'description' => $dataCategory['description'],
            ]);
            foreach ($dataCategory['exercises'] as $order => $dataExercise) {
                $exercise = Exercise::withTrashed()->firstOrCreate([
                    'workout_category_id' => $category->id, 'slug' => Str::slug($dataExercise['name']),
                ], [...$dataExercise, 'sort_order' => $order]);
                WorkoutPlanExercise::firstOrCreate([
                    'workout_plan_id' => $plan->id, 'exercise_id' => $exercise->id,
                ], ['sets' => $dataExercise['sets'], 'reps' => $dataExercise['reps'], 'sort_order' => $order]);
            }
        }
    }

    private function importDiets(): void
    {
        $data = json_decode(file_get_contents(public_path('data/diet-plans.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($data['diets'] as $order => $dataDiet) {
            $slug = $dataDiet['id'];
            unset($dataDiet['id']);
            // Keep any later owner edits when the importer is run again.
            DietPlan::withTrashed()->firstOrCreate(['slug' => $slug], [...$dataDiet, 'sort_order' => $order]);
        }
    }
}
