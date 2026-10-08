<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Settings\PlayerMode;
use App\Settings\SettingKey;
use App\SettingsWindow\SettingsService;
use App\SettingsWindow\SettingsValues;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What the settings window may save. Requirements 7.2.
 *
 * A genre or mood has to be one the catalog has, or null for "not chosen". The
 * catalog URL has to be https, because the app downloads code-free data from it
 * with no other check on where it came from.
 */
final class SaveSettingsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(SettingsService $settings): array
    {
        return [
            'defaultGenre' => ['nullable', 'string', Rule::in($settings->genreIds())],
            'defaultMood' => ['nullable', 'string', Rule::in($settings->moodIds())],
            'playerMode' => ['required', Rule::enum(PlayerMode::class)],
            'reminderEnabled' => ['required', 'boolean'],
            'reminderTime' => ['required', 'string', 'regex:'.SettingKey::REMINDER_TIME_PATTERN],
            'weekdaysOnly' => ['required', 'boolean'],
            'launchAtLogin' => ['required', 'boolean'],
            'catalogUrl' => ['required', 'string', 'max:2048', 'url:https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'defaultGenre.in' => 'Choose a genre from the catalog.',
            'defaultMood.in' => 'Choose a mood from the catalog.',
            'playerMode.required' => 'Choose how to play a playlist.',
            'playerMode.enum' => 'Choose how to play a playlist.',
            'reminderTime.required' => 'Use a 24 hour time, like 09:00.',
            'reminderTime.regex' => 'Use a 24 hour time, like 09:00.',
            'catalogUrl.required' => 'Use a link that starts with https://.',
            'catalogUrl.url' => 'Use a link that starts with https://.',
        ];
    }

    public function values(): SettingsValues
    {
        $genre = $this->validated('defaultGenre');
        $mood = $this->validated('defaultMood');

        return new SettingsValues(
            defaultGenre: is_string($genre) ? $genre : null,
            defaultMood: is_string($mood) ? $mood : null,
            playerMode: PlayerMode::from($this->string('playerMode')->toString()),
            reminderEnabled: $this->boolean('reminderEnabled'),
            reminderTime: $this->string('reminderTime')->toString(),
            weekdaysOnly: $this->boolean('weekdaysOnly'),
            launchAtLogin: $this->boolean('launchAtLogin'),
            catalogUrl: $this->string('catalogUrl')->toString(),
        );
    }
}
