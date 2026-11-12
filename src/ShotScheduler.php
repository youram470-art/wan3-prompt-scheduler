<?php

declare(strict_types=1);

namespace Wan3\PromptScheduler;

use InvalidArgumentException;

/**
 * Turn a beat sheet into a shot plan for a text-to-video batch: timing,
 * prompt, aspect ratio and a reproducible seed per clip.
 */
final class ShotScheduler
{
    public const RATIOS = [
        '16:9' => [1280, 720],
        '9:16' => [720, 1280],
        '1:1' => [960, 960],
        '4:3' => [1024, 768],
        '3:4' => [768, 1024],
        '21:9' => [1344, 576],
    ];

    public const MIN_SECONDS = 2;
    public const MAX_SECONDS = 10;

    private int $seconds;
    private string $ratio;

    public function __construct(int $seconds = 5, string $ratio = '16:9')
    {
        if (!isset(self::RATIOS[$ratio])) {
            throw new InvalidArgumentException(sprintf('unsupported aspect ratio: %s', $ratio));
        }

        $this->seconds = max(self::MIN_SECONDS, min(self::MAX_SECONDS, $seconds));
        $this->ratio = $ratio;
    }

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function aspect(): string
    {
        return $this->ratio;
    }

    /**
     * @param string[] $beats one beat per clip
     * @return array<int, array<string, mixed>>
     */
    public function plan(array $beats, ?string $camera = null, ?string $style = null): array
    {
        $plan = [];

        foreach (array_values($beats) as $index => $beat) {
            $parts = [$beat];
            if ($camera !== null && trim($camera) !== '') {
                $parts[] = trim($camera);
            }
            if ($style !== null && trim($style) !== '') {
                $parts[] = trim($style);
            }

            $prompt = implode(', ', $parts);
            [$width, $height] = self::RATIOS[$this->ratio];

            $plan[] = [
                'index' => $index + 1,
                'start' => $index * $this->seconds,
                'end' => ($index + 1) * $this->seconds,
                'seconds' => $this->seconds,
                'aspect' => $this->ratio,
                'resolution' => sprintf('%dx%d', $width, $height),
                'seed' => self::seed($prompt),
                'prompt' => $prompt,
            ];
        }

        return $plan;
    }

    /** @return array<string, int|float> */
    public static function summary(array $plan): array
    {
        if ($plan === []) {
            return ['shots' => 0, 'runtime' => 0];
        }

        $last = $plan[count($plan) - 1];

        return [
            'shots' => count($plan),
            'runtime' => $last['end'],
            'seconds_per_clip' => $last['seconds'],
        ];
    }

    /**
     * Stable 31-bit seed for a prompt, so the same prompt reproduces the same
     * render.
     */
    public static function seed(string $prompt): int
    {
        return hexdec(substr(hash('sha256', $prompt), 0, 8)) & 0x7FFFFFFF;
    }

    /** Read a beat sheet: one beat per line, "#" starts a comment. */
    public static function readBeats(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new InvalidArgumentException(sprintf('cannot read %s', $path));
        }

        $beats = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $beats[] = $line;
        }

        return $beats;
    }
}
