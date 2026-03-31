<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MotivationalQuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $greetings = [
            ['text' => '¡Qué alegría verte de nuevo por aquí!', 'type' => 'greeting'],
            ['text' => '¡Hola! Qué bien que hayas vuelto. ¡Hoy va a ser un gran día!', 'type' => 'greeting'],
            ['text' => '¡Bienvenido! Tu equipo y tus tareas te han echado de menos.', 'type' => 'greeting'],
            ['text' => '¡Hola! Prepárate, porque hoy vas a conseguir cosas increíbles.', 'type' => 'greeting'],
            ['text' => '¡Qué bueno tenerte de vuelta! Vamos a por todas.', 'type' => 'greeting'],
            ['text' => '¡Ánimo! Hoy es una nueva oportunidad para brillar.', 'type' => 'greeting'],
            ['text' => '¡Bienvenido de nuevo! Tu energía es lo que mueve Sientia.', 'type' => 'greeting'],
        ];

        $quotes = [
            ['text' => 'El éxito es la suma de pequeños esfuerzos repetidos día tras día.', 'author' => 'Robert Collier', 'type' => 'quote'],
            ['text' => 'La única forma de hacer un gran trabajo es amar lo que haces.', 'author' => 'Steve Jobs', 'type' => 'quote'],
            ['text' => 'No cuentes los días, haz que los días cuenten.', 'author' => 'Muhammad Ali', 'type' => 'quote'],
            ['text' => 'La productividad nunca es un accidente. Siempre es el resultado de un compromiso con la excelencia.', 'author' => 'Paul J. Meyer', 'type' => 'quote'],
            ['text' => 'Empieza donde estás. Usa lo que tienes. Haz lo que puedas.', 'author' => 'Arthur Ashe', 'type' => 'quote'],
            ['text' => 'Tu actitud, no tu aptitud, determinará tu altitud.', 'author' => 'Zig Ziglar', 'type' => 'quote'],
            ['text' => 'El secreto para salir adelante es comenzar.', 'author' => 'Mark Twain', 'type' => 'quote'],
            ['text' => 'Lo que haces hoy puede mejorar todos tus mañanas.', 'author' => 'Ralph Marston', 'type' => 'quote'],
            ['text' => 'Cree que puedes y ya habrás recorrido la mitad del camino.', 'author' => 'Theodore Roosevelt', 'type' => 'quote'],
            ['text' => 'La mejor preparación para el mañana es hacer lo mejor posible hoy.', 'author' => 'H. Jackson Brown, Jr.', 'type' => 'quote'],
        ];

        foreach (array_merge($greetings, $quotes) as $item) {
            \App\Models\MotivationalQuote::create($item);
        }
    }
}
