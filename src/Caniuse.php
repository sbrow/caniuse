<?php

namespace Sbrow\Caniuse;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class Caniuse
{
    public function __construct(
        public string $diskname = 'local',
        public string $path = 'caniuse.json'
    ){}

    protected function data(?string $key = null): array {
        $disk = Storage::disk($this->diskname);

        if (!$disk->exists($this->path)) {
            $this->update();
        }

        $raw_data = $disk->get($this->path);
        $json_data = json_decode($raw_data, true);

        if ($key) {
            return data_get($json_data, $key);
        }

        return $json_data;
    }

    protected function supportTable(string $feature, string $browser): Collection {
        $key = "data.$feature.stats";
        if ($browser != '') {
            $key .= ".$browser";
        }
        return collect($this->data($key));
    }

    protected function supportedVersions(string $feature, string $browser): Collection {
        return $this->supportTable($feature, $browser)->filter(fn($value, $key) => Str::contains($value, 'y'))
            ->keys()
            ->map(function($item) {
                if (Str::contains($item, '-')) {
                    [$start, $end] = explode('-', $item);
                    try {
                        $item = range($start, $end, 0.1);
                    } catch (\Throwable $exception) {
                        $item = [$start, $end];
                    }
                }

                return $item;
            })
            ->flatten();
    }

    protected function filterBrowserName(string $browser): string {
       return $browser;
    }

    // Build wonderful things
    protected function getBrowser(string $userAgent = null) {
        if (is_null($userAgent)) {
            $userAgent = request()->userAgent();
        }
        $agent = app(Agent::class);
        $agent->setUserAgent($userAgent);

        $browser = Str::lower($agent->browser());
        $version = $agent->version($agent->browser());

        $browser = match($browser) {
            'opera mini' => 'op_mini',
            default => $browser,
        };

        if ($agent->is('IE') && $agent->isMobile()) {
            return ['ie_mob', $version];
        }
        if ($agent->is('Opera') && $agent->isMobile()) {
            return ['op_mob', $version];
        }
        if ($agent->is('iOS') && $agent->browser() == 'Safari') {
            return ['ios_saf', $version];
        }
        if ($agent->is('iOS') && $agent->browser() == 'Chrome') {
            return ['ios_saf', $agent->version($agent->platform())];
        }
        if ($agent->is('Android'))
            if ($agent->is('Chrome')) {
                return ['and_chr', $version];
            } elseif($agent->is('Firefoex')) {
                return ['and_ff', $version];
        }

        return [$browser, $version];
    }

    public function features(?string $match = null): Collection {
        $features = collect($this->data('data'))->keys();

        if (is_null($match)) {
            return $features;
        }

        return $features->filter(fn($feature) => Str::contains($feature, [$match]));
    }

    public function hasFeature(string $feature): bool {
        return $this->features()->contains($feature);
    }

    public function update() {
        $url = 'https://raw.githubusercontent.com/Fyrd/caniuse/main/data.json';

        $data = Http::get($url);

        Storage::disk($this->diskname)->put($this->path, $data);
    }

    public function __call(string $name, array $arguments)
    {
        $feature = Str::kebab($name);

        // Check that feature exists
        if(!$this->hasFeature($feature)) {
            $feature = Str::remove('-', $feature);
        }
        if(!$this->hasFeature($feature)) {
            throw new \Exception("Feature '$name' does not exist");
        }

        [$userAgent] = count($arguments) > 0 ? $arguments : [null];
        [$browser, $browserVersion] = $this->getBrowser($userAgent);

        $allowed = $this
            ->supportedVersions($feature, $browser)
//            ->contains(fn($value, $key) => Str::startsWith($browserVersion, $value));
        ->contains(function($value, $key) use ($browserVersion) {
            $check = Str::substrCount($value, '.');
            $version = Str::of($browserVersion)->version();
            while ($version->substrCount('.') > $check) {
                $version = $version->beforeLast('.');
            }

            return version_compare($version, $value, '==');
        });

        Log::debug('', [$browser, $browserVersion, $allowed]);

        return $allowed;
    }
}
