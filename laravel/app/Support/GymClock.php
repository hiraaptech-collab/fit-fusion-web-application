<?php
namespace App\Support;
use App\Models\GymSetting;
use Illuminate\Support\Carbon;
class GymClock {
    public static function timezone(): string {
        $request=app('request');
        if(!$request->attributes->has('gym_timezone'))$request->attributes->set('gym_timezone',GymSetting::find(1)?->timezone ?: config('app.timezone'));
        return $request->attributes->get('gym_timezone');
    }
    public static function today(): Carbon {
        // Calendar dates belong to the gym; database timestamps continue to use UTC.
        $date = Carbon::now(self::timezone())->toDateString();
        return Carbon::parse($date, config('app.timezone'))->startOfDay();
    }
    public static function utcInput(mixed $value): mixed {
        if (!is_string($value) || trim($value)==="") return $value;
        try { return Carbon::parse($value, self::timezone())->utc()->format('Y-m-d H:i:s'); }
        catch (\Throwable $error) { return $value; } // The FormRequest reports invalid dates.
    }
    public static function display($date, string $format): ?string {
        return $date?->copy()->timezone(self::timezone())->format($format);
    }
}


