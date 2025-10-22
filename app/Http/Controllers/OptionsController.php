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
        ]);
    }

    /**
     * Process change username submit request.
     *
     * @param Request $request
     * @param PlayerService $player
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
                return ['error' => $validationResult['error']];
            }

            // Update username
            $player->setUsername($name);
            $player->save();
        }

        return ['success' => __('Settings saved')];
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
        // ✅ NEW: explicit log that we entered the method
        \Log::info('[OptionsController] save() called');

        // ✅ existing log of request contents
        \Log::info('[OptionsController] OPTIONS SAVE REQUEST', $request->all());

        // Define change handlers.
        $change_handlers = [
            'processChangeUsername',
            'processChangePassword'
        ];

        // Loop through change handlers, execute them and if it triggers
        // return its message.
        foreach ($change_handlers as $method) {
            $change_handler = $this->{$method}($request, $player);
            if ($change_handler) {
                if (!empty($change_handler['success_logout'])) {
                    return redirect()->route('options.index')->with('success_logout', $change_handler['success_logout']);
                }

                if (!empty($change_handler['success'])) {
                    return redirect()->route('options.index')->with('success', $change_handler['success']);
                }

                if (!empty($change_handler['error'])) {
                    return redirect()->route('options.index')->with('error', $change_handler['error']);
                }
            }
        }

        // No actual change has been detected, return to index page.
        return redirect()->route('options.index');
    }

    /**
     * Process change password submit request.
     *
     * @param Request $request
     * @param PlayerService $player
     * @return array<string,string>
     */
    public function processChangePassword(Request $request, PlayerService $player): array
    {
        // ✅ NEW: explicit log that we entered the method + request fields
        \Log::info('[OptionsController] processChangePassword called', $request->all());

        $currentPassword = $request->input('db_password');
        $newPassword1 = $request->input('newpass1');
        $newPassword2 = $request->input('newpass2');

        if (empty($newPassword1) || empty($newPassword2)) {
            return [];
        }

        if ($newPassword1 !== $newPassword2) {
            return ['error' => __('The new passwords do not match.')];
        }

        if (!\Hash::check($currentPassword, $player->getPassword())) {
            return ['error' => __('The current password is incorrect.')];
        }

        if (strlen($newPassword1) < 4 || strlen($newPassword1) > 255) {
            return ['error' => __('Password must be between 4 and 255 characters.')];
        }

        $player->setPassword(\Hash::make($newPassword1));
        $player->save();

        \Log::info('[OptionsController] Password updated for user ID: ' . $player->getId());

        return ['success' => __('Password successfully changed.')];
    }
}
