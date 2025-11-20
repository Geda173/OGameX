<?php

namespace OGame\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use OGame\Http\Controllers\OGameController;
use OGame\Services\PlayerService;
use OGame\Services\SettingsService;

class ServerSettingsController extends OGameController
{
    /**
     * Shows the server settings page.
     *
     * @param PlayerService $player
     * @param SettingsService $settingsService
     * @return View
     */
    public function index(PlayerService $player, SettingsService $settingsService): View
    {
        return view('ingame.admin.serversettings')->with([
            'fleet_speed' => $settingsService->fleetSpeed(),
            'economy_speed' => $settingsService->economySpeed(),
            'research_speed' => $settingsService->researchSpeed(),
            'basic_income_metal' => $settingsService->basicIncomeMetal(),
            'basic_income_crystal' => $settingsService->basicIncomeCrystal(),
            'basic_income_deuterium' => $settingsService->basicIncomeDeuterium(),
            'basic_income_energy' => $settingsService->basicIncomeEnergy(),
            'registration_planet_amount' => $settingsService->registrationPlanetAmount(),
            'universe_name' => $settingsService->universeName(),
            'planet_fields_bonus' => $settingsService->planetFieldsBonus(),
            'dark_matter_bonus' => $settingsService->darkMatterBonus(),
            'alliance_combat_system_on' => $settingsService->allianceCombatSystemOn(),
            'debris_field_from_ships' => $settingsService->debrisFieldFromShips(),
            'debris_field_from_defense' => $settingsService->debrisFieldFromDefense(),
            'debris_field_deuterium_on' => $settingsService->debrisFieldDeuteriumOn(),
            'maximum_moon_chance' => $settingsService->maximumMoonChance(),
            'ignore_empty_systems_on' => $settingsService->ignoreEmptySystemsOn(),
            'ignore_inactive_systems_on' => $settingsService->ignoreInactiveSystemsOn(),
            'number_of_galaxies' => $settingsService->numberOfGalaxies(),
            'battle_engine' => $settingsService->battleEngine(),
            'bonus_expedition_slots' => $settingsService->bonusExpeditionSlots(),
            'expedition_reward_multiplier_resources' => $settingsService->expeditionRewardMultiplierResources(),
            'expedition_reward_multiplier_ships' => $settingsService->expeditionRewardMultiplierShips(),
            'expedition_reward_multiplier_dark_matter' => $settingsService->expeditionRewardMultiplierDarkMatter(),
            'expedition_reward_multiplier_items' => $settingsService->expeditionRewardMultiplierItems(),
            'expedition_weight_ships' => $settingsService->expeditionWeightShips(),
            'expedition_weight_resources' => $settingsService->expeditionWeightResources(),
            'expedition_weight_delay' => $settingsService->expeditionWeightDelay(),
            'expedition_weight_speedup' => $settingsService->expeditionWeightSpeedup(),
            'expedition_weight_nothing' => $settingsService->expeditionWeightNothing(),
            'expedition_weight_black_hole' => $settingsService->expeditionWeightBlackHole(),
            'expedition_weight_pirates' => $settingsService->expeditionWeightPirates(),
            'expedition_weight_aliens' => $settingsService->expeditionWeightAliens(),
            'expedition_weight_dark_matter' => $settingsService->expeditionWeightDarkMatter(),
            'expedition_weight_merchant' => $settingsService->expeditionWeightMerchant(),
        ]);
    }

    /**
     * Updates the server settings.
     *
     * @param SettingsService $settingsService
     * @return RedirectResponse
     */
    public function update(SettingsService $settingsService): RedirectResponse
    {
        $settingsService->set('fleet_speed', request('fleet_speed'));
        $settingsService->set('economy_speed', request('economy_speed'));
        $settingsService->set('research_speed', request('research_speed'));

        $settingsService->set('basic_income_metal', request('basic_income_metal'));
        $settingsService->set('basic_income_crystal', request('basic_income_crystal'));
        $settingsService->set('basic_income_deuterium', request('basic_income_deuterium'));
        $settingsService->set('basic_income_energy', request('basic_income_energy'));

        $settingsService->set('registration_planet_amount', request('registration_planet_amount'));

        $settingsService->set('planet_fields_bonus', request('planet_fields_bonus'));
        $settingsService->set('dark_matter_bonus', request('dark_matter_bonus'));
        $settingsService->set('alliance_combat_system_on', request('alliance_combat_system_on', 0));
        $settingsService->set('debris_field_from_ships', request('debris_field_from_ships'));
        $settingsService->set('debris_field_from_defense', request('debris_field_from_defense'));
        $settingsService->set('debris_field_deuterium_on', request('debris_field_deuterium_on', 0));
        $settingsService->set('maximum_moon_chance', request('maximum_moon_chance'));

        $settingsService->set('ignore_empty_systems_on', request('ignore_empty_systems_on', 0));
        $settingsService->set('ignore_inactive_systems_on', request('ignore_inactive_systems_on', 0));
        $settingsService->set('number_of_galaxies', request('number_of_galaxies'));

        $settingsService->set('battle_engine', request('battle_engine'));

        // Expedition event settings
        $settingsService->set('bonus_expedition_slots', request('bonus_expedition_slots', 0));
        $settingsService->set('expedition_reward_multiplier_resources', request('expedition_reward_multiplier_resources', 1.0));
        $settingsService->set('expedition_reward_multiplier_ships', request('expedition_reward_multiplier_ships', 1.0));
        $settingsService->set('expedition_reward_multiplier_dark_matter', request('expedition_reward_multiplier_dark_matter', 1.0));
        $settingsService->set('expedition_reward_multiplier_items', request('expedition_reward_multiplier_items', 1.0));
        $settingsService->set('expedition_weight_ships', request('expedition_weight_ships', 24));
        $settingsService->set('expedition_weight_resources', request('expedition_weight_resources', 34));
        $settingsService->set('expedition_weight_delay', request('expedition_weight_delay', 9));
        $settingsService->set('expedition_weight_speedup', request('expedition_weight_speedup', 4));
        $settingsService->set('expedition_weight_nothing', request('expedition_weight_nothing', 29));
        $settingsService->set('expedition_weight_black_hole', request('expedition_weight_black_hole', 1));
        $settingsService->set('expedition_weight_pirates', request('expedition_weight_pirates', 0));
        $settingsService->set('expedition_weight_aliens', request('expedition_weight_aliens', 0));
        $settingsService->set('expedition_weight_dark_matter', request('expedition_weight_dark_matter', 0));
        $settingsService->set('expedition_weight_merchant', request('expedition_weight_merchant', 0));

        return redirect()->route('admin.serversettings.index')->with('success', __('Changes saved!'));
    }
}
