<?php

namespace OGame\Console\Commands;

use Illuminate\Console\Command;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Highscore;
use OGame\Models\User;
use OGame\Services\HighscoreService;

class GenerateHighscores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ogamex:generate-highscores';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates Highscore data';

    /**
     * Execute the console command.
     */
    public function handle(HighscoreService $highscoreService, PlayerServiceFactory $playerServiceFactory): void
    {
        // FIXED: Skip users marked as highscore_exempt (e.g., test accounts, admins)
        $users = User::query()
            ->whereHas('tech')
            ->where('highscore_exempt', false);

        $this->info('Updating highscores...');
        $bar = $this->output->createProgressBar();
        $bar->start($users->count());
        $users->chunk(200, function ($players) use ($playerServiceFactory, $highscoreService, &$bar) {
            foreach ($players as $player) {
                $playerService = $playerServiceFactory->make($player->id);
                Highscore::updateOrCreate(['player_id' => $player->id], [
                    'general' => $highscoreService->getPlayerScore($playerService),
                    'economy' => $highscoreService->getPlayerScoreEconomy($playerService),
                    'research' => $highscoreService->getPlayerScoreResearch($playerService),
                    'military' => $highscoreService->getPlayerScoreMilitary($playerService),
                ]);
                $bar->advance();
            }
        });
        $this->info('All highscores completed!');
    }
}
