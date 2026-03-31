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
            ['text' => '¡Hola, Guerrero de la Productividad! La matriz te espera.', 'type' => 'greeting'],
            ['text' => '¡Buenos días! Tu enfoque hoy determinará tu éxito mañana.', 'type' => 'greeting'],
            ['text' => '¡Hola! Recuerda que cada tarea terminada es un paso hacia tu meta.', 'type' => 'greeting'],
            ['text' => '¡Bienvenido! Hagamos de hoy algo memorable.', 'type' => 'greeting'],
            ['text' => '¡Hola! Tu determinación es contagiosa. ¡A por ello!', 'type' => 'greeting'],
            ['text' => '¡Qué placer saludarte! Tu tablero está listo para la acción.', 'type' => 'greeting'],
            ['text' => '¡Hola! Hoy es el día perfecto para tachar ese "Pendiente".', 'type' => 'greeting'],
            ['text' => '¡Saludos! La constancia es tu mejor aliada hoy.', 'type' => 'greeting'],
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
            ['text' => 'No dejes que el ayer ocupe demasiado del hoy.', 'author' => 'Will Rogers', 'type' => 'quote'],
            ['text' => 'Si quieres que algo se haga, dáselo a una persona ocupada.', 'author' => 'Benjamin Franklin', 'type' => 'quote'],
            ['text' => 'La disciplina es el puente entre las metas y los logros.', 'author' => 'Jim Rohn', 'type' => 'quote'],
            ['text' => 'Hazlo o no lo hagas, pero no lo intentes.', 'author' => 'Yoda', 'type' => 'quote'],
            ['text' => 'Tu tiempo es limitado, así que no lo desperdicies viviendo la vida de otra persona.', 'author' => 'Steve Jobs', 'type' => 'quote'],
            ['text' => 'La motivación es lo que te pone en marcha. El hábito es lo que te mantiene.', 'author' => 'Jim Ryun', 'type' => 'quote'],
            ['text' => 'Los aficionados esperan a la inspiración, los demás simplemente nos levantamos y nos ponemos a trabajar.', 'author' => 'Philip Glass', 'type' => 'quote'],
            ['text' => 'El hombre que mueve una montaña comienza cargando pequeñas piedras.', 'author' => 'Confucio', 'type' => 'quote'],
            ['text' => 'La calidad no es un acto, es un hábito.', 'author' => 'Aristóteles', 'type' => 'quote'],
            ['text' => 'Si te ofrecen un asiento en un cohete, no preguntes qué asiento es. ¡Simplemente sube!', 'author' => 'Sheryl Sandberg', 'type' => 'quote'],
            ['text' => 'El futuro depende de lo que hagas hoy.', 'author' => 'Mahatma Gandhi', 'type' => 'quote'],
            ['text' => 'No se trata de tener tiempo, se trata de hacer tiempo.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'Tu mente es para tener ideas, no para guardarlas.', 'author' => 'David Allen', 'type' => 'quote'],
            ['text' => 'El trabajo duro supera al talento cuando el talento no trabaja duro.', 'author' => 'Tim Notke', 'type' => 'quote'],
            ['text' => 'Todo lo que siempre has querido está al otro lado del miedo.', 'author' => 'George Addair', 'type' => 'quote'],
            ['text' => 'La felicidad no es algo hecho. Viene de tus propias acciones.', 'author' => 'Dalai Lama', 'type' => 'quote'],
            ['text' => 'La mejor forma de predecir el futuro es creándolo.', 'author' => 'Peter Drucker', 'type' => 'quote'],
            ['text' => 'Los límites solo existen en nuestra mente.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'Un viaje de mil millas comienza con un solo paso.', 'author' => 'Lao Tzu', 'type' => 'quote'],
            ['text' => 'No busques errores, busca soluciones.', 'author' => 'Henry Ford', 'type' => 'quote'],
            ['text' => 'La perseverancia es el trabajo duro que haces después de cansarte del trabajo duro que ya hiciste.', 'author' => 'Newt Gingrich', 'type' => 'quote'],
            ['text' => 'Si no estás dispuesto a arriesgar lo inusual, tendrás que acostumbrarte a lo ordinario.', 'author' => 'Jim Rohn', 'type' => 'quote'],
            ['text' => 'Las oportunidades no ocurren, las creas tú.', 'author' => 'Chris Grosser', 'type' => 'quote'],
            ['text' => 'El éxito no es el final, el fracaso no es fatal: es el coraje para continuar lo que cuenta.', 'author' => 'Winston Churchill', 'type' => 'quote'],
            ['text' => 'Nada es imposible, la propia palabra lo dice: ¡soy posible!', 'author' => 'Audrey Hepburn', 'type' => 'quote'],
            ['text' => 'Tanto si piensas que puedes, como si piensas que no puedes, estás en lo cierto.', 'author' => 'Henry Ford', 'type' => 'quote'],
            ['text' => 'Haz de cada día tu obra maestra.', 'author' => 'John Wooden', 'type' => 'quote'],
            ['text' => 'La productividad es ser capaz de hacer cosas que antes no podías hacer.', 'author' => 'Franz Kafka', 'type' => 'quote'],
            ['text' => 'Solo yo puedo cambiar mi vida. Nadie puede hacerlo por mí.', 'author' => 'Carol Burnett', 'type' => 'quote'],
            ['text' => 'El pesimista ve dificultad en toda oportunidad. El optimista ve oportunidad en toda dificultad.', 'author' => 'Winston Churchill', 'type' => 'quote'],
            ['text' => 'No te detengas cuando estés cansado. Detente cuando hayas terminado.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'Si te caes siete veces, levántate ocho.', 'author' => 'Proverbio japonés', 'type' => 'quote'],
            ['text' => 'Lo que no nos mata, nos hace más fuertes.', 'author' => 'Friedrich Nietzsche', 'type' => 'quote'],
            ['text' => 'El éxito es gustarte a ti mismo, gustarte lo que haces y gustarte cómo lo haces.', 'author' => 'Maya Angelou', 'type' => 'quote'],
            ['text' => 'La verdadera motivación viene del logro, del desarrollo personal, de la satisfacción en el trabajo y del reconocimiento.', 'author' => 'Frederick Herzberg', 'type' => 'quote'],
            ['text' => 'Si puedes soñarlo, puedes hacerlo.', 'author' => 'Walt Disney', 'type' => 'quote'],
            ['text' => 'No dejes que lo que no puedes hacer interfiera con lo que puedes hacer.', 'author' => 'John Wooden', 'type' => 'quote'],
            ['text' => 'La excelencia no es una habilidad, es una actitud.', 'author' => 'Ralph Marston', 'type' => 'quote'],
            ['text' => 'El fracaso es simplemente la oportunidad de comenzar de nuevo, esta vez de forma más inteligente.', 'author' => 'Henry Ford', 'type' => 'quote'],
            ['text' => 'La victoria más difícil es la victoria sobre uno mismo.', 'author' => 'Aristóteles', 'type' => 'quote'],
            ['text' => 'El único lugar donde el éxito viene antes que el trabajo es en el diccionario.', 'author' => 'Vidal Sassoon', 'type' => 'quote'],
            ['text' => 'No dejes que los ruidos de las opiniones ajenas acallen tu propia voz interior.', 'author' => 'Steve Jobs', 'type' => 'quote'],
            ['text' => 'La grandeza nace de pequeños comienzos.', 'author' => 'Sir Francis Drake', 'type' => 'quote'],
            ['text' => 'Cada día es una nueva oportunidad para elegir tu actitud.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'La productividad no se trata de hacer todo, sino de hacer lo correcto.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'El hombre sabio no espera que las cosas ocurran, él hace que ocurran.', 'author' => 'Desconocido', 'type' => 'quote'],
            ['text' => 'Tu futuro se crea por lo que haces hoy, no mañana.', 'author' => 'Robert Kiyosaki', 'type' => 'quote'],
            ['text' => 'La simplicidad es la máxima sofisticación.', 'author' => 'Leonardo da Vinci', 'type' => 'quote'],
            ['text' => 'Un objetivo sin un plan es solo un deseo.', 'author' => 'Antoine de Saint-Exupéry', 'type' => 'quote'],
            ['text' => 'La acción es la clave fundamental para todo éxito.', 'author' => 'Pablo Picasso', 'type' => 'quote'],
        ];

        foreach (array_merge($greetings, $quotes) as $item) {
            \App\Models\MotivationalQuote::firstOrCreate(
                ['text' => $item['text']],
                $item
            );
        }
    }
}
