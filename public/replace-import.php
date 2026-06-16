<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$stagiaires = $input['stagiaires'] ?? [];

if (!is_array($stagiaires) || empty($stagiaires)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Aucune donnée reçue.']);
    exit;
}

try {
    \DB::beginTransaction();
    \DB::statement('SET FOREIGN_KEY_CHECKS=0');
    \App\Models\Attendance::query()->delete();
    \App\Models\Inscription::query()->delete();
    \App\Models\Stagiaire::query()->delete();
    \DB::statement('SET FOREIGN_KEY_CHECKS=1');

    $imported = 0;
    $errors = 0;

    foreach ($stagiaires as $data) {
        try {
            if (!isset($data['matricule']) || !isset($data['nom']) || !isset($data['prenom'])) {
                $errors++;
                continue;
            }

            $codeDiplome = $data['code_diplome'] ?? null;
            $dateInscription = $data['date_inscription'] ?? null;
            $dateDossierComplet = $data['date_dossier_complet'] ?? null;

            unset($data['code_diplome'], $data['date_inscription'], $data['date_dossier_complet']);

            foreach (['date_naissance'] as $f) {
                if (isset($data[$f]) && ($data[$f] === '' || $data[$f] === 'null' || $data[$f] === 'Invalid Date')) {
                    $data[$f] = null;
                }
            }

            $stagiaire = \App\Models\Stagiaire::create($data);

            if ($codeDiplome) {
                $programme = \App\Models\Programme::where('code_diplome', $codeDiplome)->first();
                if ($programme) {
                    $pivotData = [];
                    if ($dateInscription) $pivotData['date_inscription'] = $dateInscription;
                    if ($dateDossierComplet) $pivotData['date_dossier_complet'] = $dateDossierComplet;
                    $stagiaire->programmes()->attach($programme->id, $pivotData);
                }
            }

            $imported++;
        } catch (\Exception $e) {
            $errors++;
        }
    }

    \DB::commit();

    echo json_encode([
        'success' => true,
        'message' => "Remplacement terminé: $imported importés, $errors erreurs",
        'data'    => ['imported' => $imported, 'errors' => $errors],
    ]);
} catch (\Exception $e) {
    \DB::rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur: ' . $e->getMessage(),
    ]);
}
