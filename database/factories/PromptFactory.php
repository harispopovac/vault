<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Prompt;
use App\Models\Organisation;
use App\Models\User;

class PromptFactory extends Factory
{
    protected $model = Prompt::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true) . ' Prompt',
            'description' => $this->faker->sentence(),
            'field_definitions' => [
                'fields' => [
                    [
                        'id' => 'knowledge_context',
                        'type' => 'text',
                        'label' => 'What changes did you make and why?',
                        'required' => true
                    ],
                    [
                        'id' => 'technical_details',
                        'type' => 'text',
                        'label' => 'Technical implementation details',
                        'required' => false
                    ],
                    [
                        'id' => 'lessons_learned',
                        'type' => 'text',
                        'label' => 'Challenges faced or lessons learned',
                        'required' => false
                    ],
                    [
                        'id' => 'future_considerations',
                        'type' => 'text',
                        'label' => 'Future considerations',
                        'required' => false
                    ]
                ]
            ],
            'organisation_id' => Organisation::factory(),
            'created_by' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}