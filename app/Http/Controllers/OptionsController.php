<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Services\PlayerService;

class OptionsController extends OGameController
{
    /**
     * Shows the overview index page
     *
     * @param PlayerService $player
     * @return View
     */
    public function index(PlayerService $player): View
    {
        $this->setBodyId('preferences');

        $canUpdateUsername = true;
        if ($lastChange = $player->getLastUsernameChange()) {
            $canUpdateUsername = $lastChange->addWeek()->isPast();
        }

        return view('ingame.options.index')->with([
            'username' => $player->getUsername(),
            'current_email' => $player->getEmail(),
            'canUpdateUsername' => $canUpdateUsername,
            'espionage_probes_amount' => $player->getUser()->espionage_probes_amount ?? 3,
        ]);
    }

    /**
     * Process change username submit request.
     *
     * @param Request $request
     * @param PlayerService $player
     *
     * @return array<string,string>
     * @throws Exception
     */
    public function processChangeUsername(Request $request, PlayerService $player): array
    {
        $name = $request->input('new_username_username');
        if (!empty($name)) {
            // Check if username validates.
            $validationResult = $player->isUsernameValid($name);
            if (!$validationResult['valid']) {
                return array('error' => $validationResult['error']);
            }

            // Update username
            $player->setUsername($name);
            $player->save();
        }

        return array('success' => __('Settings saved'));
    }

    /**
     * Process espionage probes amount submit request.
     *
     * @param Request $request
     * @param PlayerService $player
     *
     * @return array<string,string>
     */
    public function processEspionageProbesAmount(Request $request, PlayerService $player): array
    {
        $amount = $request->input('espionage_probes_amount');
        if ($amount !== null && $amount !== '') {
            // Validate the amount is a number between 1 and 99
            $amount = (int)$amount;
            if ($amount < 1 || $amount > 99) {
                return array('error' => __('Espionage probe amount must be between 1 and 99'));
            }

            // Update espionage probes amount
            $user = $player->getUser();
            $user->espionage_probes_amount = $amount;
            $user->save();

            return array('success' => __('Settings saved'));
        }

        return array();
    }

    /**
     * Save handler for index() form.
     *
     * @param Request $request
     * @param PlayerService $player
     * @return RedirectResponse
     */
    public function save(Request $request, PlayerService $player): RedirectResponse
    {
        // Define change handlers.
        $change_handlers = [
            'processChangeUsername',
            'processEspionageProbesAmount'
        ];

        // Loop through change handlers, execute them and if it triggers
        // return its message.
        foreach ($change_handlers as $method) {
            $change_handler = $this->{$method}($request, $player);
            if ($change_handler) {
                if (!empty($change_handler['success_logout'])) {
                    return redirect()->route('options.index')->with('success_logout', $change_handler['success_logout']);
                }

                if (!empty($change_handler[ 'success'])) {
                    return redirect()->route('options.index')->with('success', $change_handler['success']);
                }

                if (!empty($change_handler[ 'error'])) {
                    return redirect()->route('options.index')->with('error', $change_handler['error']);
                }
            }
        }

        // No actual change has been detected, return to index page.
        return redirect()->route('options.index');
    }
}
