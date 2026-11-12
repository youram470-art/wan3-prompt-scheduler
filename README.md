# wan3/prompt-scheduler

Turn a beat sheet into a shot plan for a text-to-video batch: timing, prompt,
aspect ratio, resolution and a reproducible seed per clip.

```bash
composer require wan3/prompt-scheduler
```

## Usage

```php
use Wan3\PromptScheduler\ShotScheduler;

$beats = [
    'a paper boat drifting across a rain puddle',
    'city skyline at dawn, thin fog between towers',
    'close up of hands folding an origami crane',
];

$scheduler = new ShotScheduler(4, '9:16');          // 4s per clip, vertical
$plan = $scheduler->plan($beats, camera: 'slow dolly in');

foreach ($plan as $shot) {
    printf(
        "%d  %02d-%02d  seed=%d  %s\n",
        $shot['index'],
        $shot['start'],
        $shot['end'],
        $shot['seed'],
        $shot['prompt']
    );
}
```

```
1  00-04  seed=906653838  a paper boat drifting across a rain puddle, slow dolly in
2  04-08  seed=406828706  city skyline at dawn, thin fog between towers, slow dolly in
3  08-12  seed=736919430  close up of hands folding an origami crane, slow dolly in
```

A plan entry looks like this:

```php
[
    'index' => 1,
    'start' => 0,
    'end' => 4,
    'seconds' => 4,
    'aspect' => '9:16',
    'resolution' => '720x1280',
    'seed' => 906653838,
    'prompt' => 'a paper boat drifting across a rain puddle, slow dolly in',
]
```

## API

| method | meaning |
| --- | --- |
| `new ShotScheduler($seconds, $ratio)` | clip length clamped to 2-10s; ratio one of `16:9`, `9:16`, `1:1`, `4:3`, `3:4`, `21:9` |
| `plan(array $beats, ?string $camera, ?string $style)` | one entry per beat |
| `summary(array $plan)` | shot count and runtime |
| `readBeats(string $path)` | beat sheet from a file, `#` comments skipped |
| `seed(string $prompt)` | stable 31-bit seed for a prompt |

## Why plan first

Text-to-video is cheap to start and expensive to redo:

- **Timing drifts.** Eight beats at six seconds is 48 seconds, not the 30 the
  edit needs. Working the numbers out first makes that obvious.
- **Renders are not reproducible.** A seed derived from the prompt means the
  same beat produces the same clip next week, and a diff between two plans is
  readable.

Prompt conventions here follow the request format of
[Wan 3.0](https://wan-3.io/), which handles text-to-video and image-to-video.

## License

MIT
