<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportStagiaires extends Command
{
    protected $signature   = 'import:stagiaires {file : Path to the .xlsx file}';
    protected $description = 'Import stagiaires from an Excel file into the database';

    public function handle(): int
    {
        $filePath = $this->argument('file');

        if (! \file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Reading {$filePath} …");

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getSheetByName('Export') ?? $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, false);

        $header = \array_shift($rows);
        $this->info(\sprintf('  %d data rows loaded.', \count($rows)));

        $col = \array_flip($header);

        $stats = ['programmes' => 0, 'stagiaires' => 0, 'inscriptions' => 0, 'errors' => 0];
        $errors = [];

        $secteurCache   = [];
        $niveauCache    = [];
        $filiereCache   = [];
        $programmeCache = [];
        $stagiaireCache = [];

        $now = now()->toDateTimeString();

        // ── Phase 1: seed lookup tables ──────────────────────────────────────────
        $this->info('Phase 1: seeding secteurs, niveaux, filieres, programmes …');

        $uniqueProgs = [];
        foreach ($rows as $row) {
            $code    = \trim((string) ($row[$col['CodeDiplome']] ?? ''));
            $libelle = \trim((string) ($row[$col['LibelleLong']] ?? ''));
            if ($code && ! isset($uniqueProgs[$code])) {
                $uniqueProgs[$code] = $libelle;
            }
        }

        foreach ($uniqueProgs as $codeDiplome => $libelleLong) {
            try {
                $meta = $this->parseLibelle($libelleLong);

                $secteurId = $this->seedSecteur($meta['secteur_code'], $secteurCache, $now);
                $niveauId  = $this->seedNiveau($meta['niveau_code'],   $niveauCache,  $now);
                $filiereId = $this->seedFiliere($meta['filiere_code'],  $secteurId, $filiereCache, $now);

                $progId = $this->seedProgramme(
                    $codeDiplome, $libelleLong,
                    $filiereId, $niveauId, $meta, $programmeCache, $now
                );

                if ($progId) {
                    $stats['programmes']++;
                }
            } catch (\Throwable $e) {
                $errors[] = "  [PROG {$codeDiplome}] {$e->getMessage()}";
            }
        }

        $this->info(\sprintf('  OK — %d programmes ready.', \count($programmeCache)));

        // ── Phase 2: stagiaires + inscriptions ───────────────────────────────────
        $this->info('Phase 2: importing stagiaires and inscriptions …');

        $bar = $this->output->createProgressBar(\count($rows));
        $bar->start();

        foreach ($rows as $idx => $row) {
            try {
                $matricule   = \trim((string) ($row[$col['MatriculeEtudiant']] ?? ''));
                $codeDiplome = \trim((string) ($row[$col['CodeDiplome']]       ?? ''));

                if (! $matricule || ! $codeDiplome) {
                    throw new \RuntimeException('Missing matricule or code_diplome');
                }

                $programmeId = $programmeCache[$codeDiplome]
                    ?? DB::table('programmes')->where('code_diplome', $codeDiplome)->value('id');

                if (! $programmeId) {
                    throw new \RuntimeException("Programme '{$codeDiplome}' not found");
                }

                $dob = $this->parseDate($row[$col['DateNaissance']] ?? null);
                $tel = $this->parseTel($row[$col['NTelelephone']]   ?? null);
                $cin = $this->parseCin($row[$col['CIN']]            ?? null);
                $lieu = \trim((string) ($row[$col['LieuNaissance']] ?? '')) ?: null;

                if (! isset($stagiaireCache[$matricule])) {
                    $existing = DB::table('stagiaires')->where('matricule', $matricule)->first();
                    if ($existing) {
                        DB::table('stagiaires')->where('matricule', $matricule)->update([
                            'nom'             => $row[$col['Nom']]    ?? null,
                            'prenom'          => $row[$col['Prenom']] ?? null,
                            'sexe'            => $row[$col['Sexe']]   ?? null,
                            'date_naissance'  => $dob,
                            'lieu_naissance'  => $lieu,
                            'cin'             => $cin,
                            'telephone'       => $tel,
                            'updated_at'      => $now,
                        ]);
                        $stagiaireCache[$matricule] = $existing->id;
                    } else {
                        $stagiaireId = DB::table('stagiaires')->insertGetId([
                            'matricule'      => $matricule,
                            'nom'            => $row[$col['Nom']]    ?? null,
                            'prenom'         => $row[$col['Prenom']] ?? null,
                            'sexe'           => $row[$col['Sexe']]   ?? null,
                            'date_naissance' => $dob,
                            'lieu_naissance' => $lieu,
                            'cin'            => $cin,
                            'telephone'      => $tel,
                            'created_at'     => $now,
                            'updated_at'     => $now,
                        ]);
                        $stagiaireCache[$matricule] = $stagiaireId;
                        $stats['stagiaires']++;
                    }
                }

                $stagiaireId = $stagiaireCache[$matricule];

                $inscExists = DB::table('inscriptions')
                    ->where('stagiaire_id', $stagiaireId)
                    ->where('programme_id', $programmeId)
                    ->exists();

                if (! $inscExists) {
                    DB::table('inscriptions')->insert([
                        'stagiaire_id'         => $stagiaireId,
                        'programme_id'         => $programmeId,
                        'date_inscription'     => $this->parseDate($row[$col['DateInscription']]    ?? null),
                        'date_dossier_complet' => $this->parseDate($row[$col['DateDossierComplet']] ?? null),
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ]);
                    $stats['inscriptions']++;
                }
            } catch (\Throwable $e) {
                $stats['errors']++;
                $errors[] = \sprintf(
                    '  [ROW %d] matricule=%s code=%s | %s',
                    $idx,
                    $row[$col['MatriculeEtudiant']] ?? '?',
                    $row[$col['CodeDiplome']]       ?? '?',
                    $e->getMessage()
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('── Import summary ──────────────────────────');
        $this->line(\sprintf('  Rows processed  : %d', \count($rows)));
        $this->line(\sprintf('  Programmes      : %d seeded', \count($programmeCache)));
        $this->line(\sprintf('  Stagiaires      : %d inserted', $stats['stagiaires']));
        $this->line(\sprintf('  Inscriptions    : %d inserted', $stats['inscriptions']));
        $this->line(\sprintf('  Errors          : %d', $stats['errors']));

        if ($errors) {
            $this->newLine();
            $this->warn('── Error details (first 20) ────────────────');
            foreach (\array_slice($errors, 0, 20) as $e) {
                $this->warn($e);
            }
            if (\count($errors) > 20) {
                $this->warn(\sprintf('  … and %d more.', \count($errors) - 20));
            }
        }

        return $stats['errors'] > 0 ? 1 : 0;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function parseLibelle(string $libelle): array
    {
        $result = [
            'secteur_code' => null,
            'filiere_code' => null,
            'niveau_code'  => null,
            'annee'        => null,
            'is_cds'       => false,
        ];

        if (! $libelle) {
            return $result;
        }

        $prefix = \explode('-', $libelle)[0];
        $parts  = \explode('_', \trim($prefix));

        $anneeIdx = null;
        foreach ($parts as $i => $p) {
            if (\preg_match('/^\d+[A-Z]+$/', $p)) {
                $anneeIdx = $i;
                break;
            }
        }

        if ($anneeIdx === null) {
            return $result;
        }

        \preg_match('/^(\d+)/', $parts[$anneeIdx], $m);
        $result['annee'] = (int) $m[1];

        $before = \array_slice($parts, 0, $anneeIdx);
        if (isset($before[0])) $result['secteur_code'] = $before[0];
        if (isset($before[1])) $result['filiere_code'] = $before[1];
        if (isset($before[2])) $result['niveau_code']  = $before[2];

        $result['is_cds'] = \str_contains(\strtoupper($libelle), 'CDS');

        return $result;
    }

    private function parseDate(mixed $val): ?string
    {
        if ($val === null || \trim((string) $val) === '' || \in_array((string) $val, ['NaT', 'nan'], true)) {
            return null;
        }
        $s = \trim((string) $val);
        foreach (['d/m/Y H:i:s', 'd/m/Y', 'Y-m-d H:i:s', 'Y-m-d'] as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $s);
            if ($dt) {
                return $dt->format('Y-m-d');
            }
        }
        return null;
    }

    private function parseTel(mixed $val): ?string
    {
        if ($val === null || \trim((string) $val) === '') {
            return null;
        }
        $n = (string) (int) $val;
        return $n === '0' ? null : $n;
    }

    private function parseCin(mixed $val): ?string
    {
        $s = \trim((string) ($val ?? ''));
        return ($s === '' || $s === 'nan') ? null : $s;
    }

    private function seedSecteur(?string $code, array &$cache, string $now): ?int
    {
        if (! $code) return null;
        if (isset($cache[$code])) return $cache[$code];
        $row = DB::table('secteurs')->where('code', $code)->first();
        if ($row) {
            return $cache[$code] = $row->id;
        }
        return $cache[$code] = DB::table('secteurs')->insertGetId([
            'code' => $code, 'nom' => $code,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function seedNiveau(?string $code, array &$cache, string $now): ?int
    {
        if (! $code) return null;
        if (isset($cache[$code])) return $cache[$code];
        $row = DB::table('niveau_formations')->where('code', $code)->first();
        if ($row) {
            return $cache[$code] = $row->id;
        }
        return $cache[$code] = DB::table('niveau_formations')->insertGetId([
            'code' => $code, 'nom' => $code,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function seedFiliere(?string $code, ?int $secteurId, array &$cache, string $now): ?int
    {
        if (! $code) return null;
        if (isset($cache[$code])) return $cache[$code];
        $row = DB::table('filieres')->where('code', $code)->first();
        if ($row) {
            return $cache[$code] = $row->id;
        }
        return $cache[$code] = DB::table('filieres')->insertGetId([
            'code'       => $code,
            'nom'        => $code,
            'secteur_id' => $secteurId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function seedProgramme(
        string $codeDiplome, string $libelleLong,
        ?int $filiereId, ?int $niveauId,
        array $meta, array &$cache, string $now
    ): ?int {
        if (isset($cache[$codeDiplome])) return $cache[$codeDiplome];
        $row = DB::table('programmes')->where('code_diplome', $codeDiplome)->first();
        if ($row) {
            return $cache[$codeDiplome] = $row->id;
        }
        return $cache[$codeDiplome] = DB::table('programmes')->insertGetId([
            'code_diplome' => $codeDiplome,
            'libelle_long' => $libelleLong,
            'filiere_id'   => $filiereId,
            'niveau_id'    => $niveauId,
            'annee'        => $meta['annee'],
            'saison'       => $meta['annee'],
            'is_cds'       => (int) $meta['is_cds'],
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }
}
