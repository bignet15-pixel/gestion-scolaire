<?php

namespace Tests\Unit;

use App\Http\Controllers\NoteController;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class NoteAuthorizationTest extends TestCase
{
    public function test_un_enseignant_ne_peut_pas_saisir_les_notes_dune_evaluation_creee_par_un_autre(): void
    {
        $enseignant = new User(['role' => 'enseignant']);
        $enseignant->id = 10;
        $evaluation = new Evaluation(['user_id' => 20]);

        Auth::shouldReceive('user')->once()->andReturn($enseignant);

        try {
            $this->verifierAcces($evaluation);
            $this->fail('Une réponse 403 était attendue.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(
                'Vous ne pouvez saisir les notes que pour vos propres évaluations.',
                $exception->getMessage()
            );
        }
    }

    public function test_le_gestionnaire_conserve_lacces_a_toute_evaluation(): void
    {
        $gestionnaire = new User(['role' => 'gestionnaire']);
        $gestionnaire->id = 1;
        $evaluation = new Evaluation(['user_id' => 20]);

        Auth::shouldReceive('user')->once()->andReturn($gestionnaire);

        $this->verifierAcces($evaluation);
        $this->addToAssertionCount(1);
    }

    private function verifierAcces(Evaluation $evaluation): void
    {
        $methode = new ReflectionMethod(NoteController::class, 'verifierAccesEvaluation');
        $methode->invoke(new NoteController, $evaluation);
    }
}
