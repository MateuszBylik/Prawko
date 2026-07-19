<?php

namespace App\Services;

/**
 * Warstwa IRT/Rasch — prior dla pytań, których użytkownik jeszcze nie rozwiązał:
 *   p = σ(θ_u − b_q),  θ_u = umiejętność użytkownika, b_q = trudność pytania.
 *
 * Dla narzędzia jednoosobowego degraduje się łagodnie: bez tłumu wszystkie b_q
 * startują z 0 (neutralnie), a θ_u szybko dogania rzeczywisty poziom — potem i tak
 * przejmuje FSRS (dla pytań już widzianych). Aktualizacja online w stylu Elo/Rascha.
 */
class AbilityService
{
    private float $kUser;
    private float $kQuestion;
    private float $clampLimit;

    public function __construct()
    {
        $cfg = config('prawko.ability');
        $this->kUser = (float) $cfg['k_user'];
        $this->kQuestion = (float) $cfg['k_question'];
        $this->clampLimit = (float) $cfg['clamp'];
    }

    /** Prawdopodobieństwo poprawnej odpowiedzi wg modelu Rascha. */
    public function prior(float $theta, float $b): float
    {
        return $this->sigmoid($theta - $b);
    }

    /**
     * Zwraca [nowe θ_u, nowe b_q] po odpowiedzi (gradient log-loss / Elo).
     * θ rośnie przy poprawnej odpowiedzi na trudne pytanie; b rośnie przy błędach.
     *
     * @return array{0: float, 1: float}
     */
    public function update(float $theta, float $b, bool $correct): array
    {
        $p = $this->prior($theta, $b);
        $y = $correct ? 1.0 : 0.0;

        $theta = $this->clamp($theta + $this->kUser * ($y - $p));
        $b = $this->clamp($b + $this->kQuestion * ($p - $y));

        return [$theta, $b];
    }

    /**
     * Wsadowa estymata umiejętności θ z całego wzorca odpowiedzi (dla Diagnozy).
     *
     * Metoda EAP (expected a posteriori) na siatce: dla priora N(0, priorSd²) i
     * wiarygodności Rascha zwracamy średnią i odchylenie rozkładu a posteriori.
     * Bayesowsko — stabilna także przy 0%/100% poprawnych (bez ±∞), a odchylenie
     * to naturalna miara pewności oszacowania.
     *
     * @param  array<int, array{b: float, correct: bool}>  $responses
     * @return array{0: float, 1: float}  [θ_hat, θ_sd]
     */
    public function estimate(array $responses, ?float $priorSd = null): array
    {
        $priorSd = $priorSd ?? (float) (config('prawko.diagnosis.prior_sd') ?? 1.5);
        if (empty($responses)) {
            return [0.0, $priorSd];
        }

        // Siatka θ na całej dozwolonej skali logit.
        $step = 0.05;
        $grid = [];
        for ($theta = -$this->clampLimit; $theta <= $this->clampLimit + 1e-9; $theta += $step) {
            $grid[] = round($theta, 4);
        }

        // 1) log-posterior (bez normalizacji) na każdym węźle.
        $logPost = [];
        foreach ($grid as $i => $theta) {
            $lp = -0.5 * ($theta / $priorSd) ** 2; // log-prior N(0, priorSd²)
            foreach ($responses as $r) {
                $p = $this->sigmoid($theta - $r['b']);
                $lp += $r['correct'] ? log(max(1e-12, $p)) : log(max(1e-12, 1 - $p));
            }
            $logPost[$i] = $lp;
        }

        // 2) Stabilizacja (odejmij max) i momenty rozkładu a posteriori.
        $maxLp = max($logPost);
        $wSum = 0.0;
        $mean = 0.0;
        $meanSq = 0.0;
        foreach ($grid as $i => $theta) {
            $w = exp($logPost[$i] - $maxLp);
            $wSum += $w;
            $mean += $theta * $w;
            $meanSq += $theta * $theta * $w;
        }

        if ($wSum <= 0.0) {
            return [0.0, $priorSd];
        }

        $mean /= $wSum;
        $variance = max(0.0, $meanSq / $wSum - $mean * $mean);

        return [$this->clamp($mean), sqrt($variance)];
    }

    public function sigmoid(float $x): float
    {
        return 1.0 / (1.0 + exp(-$x));
    }

    private function clamp(float $v): float
    {
        return max(-$this->clampLimit, min($this->clampLimit, $v));
    }
}
