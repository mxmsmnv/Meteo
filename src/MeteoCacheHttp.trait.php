<?php namespace ProcessWire;

trait MeteoCacheHttp {

    public function clearCache(string $key = ''): void {
        $dir = $this->cachePath();
        if (!is_dir($dir)) return;
        if ($key) {
            $file = $dir . $this->cacheFilename($key);
            if (file_exists($file)) @unlink($file);
            return;
        }
        foreach (glob($dir . 'mt_*.json') ?: [] as $f) @unlink($f);
    }

    public function cachePath(): string {
        $path = $this->wire('config')->paths->cache . 'Meteo/';
        if (!is_dir($path)) wireMkdir($path);
        return $path;
    }

    protected function cacheFilename(string $key): string {
        return 'mt_' . md5($key) . '.json';
    }

    public function cacheGet(string $key, int $cacheTime = 0): array|false {
        $file = $this->cachePath() . $this->cacheFilename($key);
        if (!file_exists($file)) return false;
        $ttl = $cacheTime > 0 ? $cacheTime : (int)($this->moduleOptions()['cache_time']);
        clearstatcache(true, $file);
        if ((time() - filemtime($file)) > $ttl) {
            @unlink($file);
            return false;
        }
        $contents = file_get_contents($file);
        if ($contents === false) return false;
        $data = json_decode($contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            @unlink($file);
            $this->lastError = 'Cache JSON decode failed: ' . json_last_error_msg();
            return false;
        }
        return is_array($data) ? $data : false;
    }

    public function cacheSet(string $key, array $data, int $seconds = 0): void {
        $dir = $this->cachePath();
        $file = $dir . $this->cacheFilename($key);
        $tmp = tempnam($dir, 'mt_tmp_');
        if ($tmp === false) return;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $this->lastError = 'JSON encode failed: ' . json_last_error_msg();
            @unlink($tmp);
            return;
        }
        $written = file_put_contents($tmp, $json, LOCK_EX);
        if ($written === false) { @unlink($tmp); return; }
        if (!rename($tmp, $file)) @unlink($tmp);
    }

    public function httpGet(string $url, int $maxRetries = 2): string|false {
        $baseDelay = 500000; // 500ms

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            if ($attempt > 0) {
                usleep($baseDelay * (2 ** ($attempt - 1)));
            }

            $ch = curl_init($url);
            if ($ch === false) {
                $this->lastError = 'curl_init failed';
                return false;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_USERAGENT      => 'Meteo/1.0 ProcessWire-Module (+https://github.com/mxmsmnv/Meteo)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $result = curl_exec($ch);
            $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err    = curl_error($ch);

            if ($result === false) {
                $this->lastError = 'cURL error: ' . $err;
                if ($attempt < $maxRetries) continue;
                return false;
            }

            if ($code === 200) {
                $this->lastError = null;
                return $result;
            }

            $this->lastError = "HTTP {$code}";
            if ($code === 429 || $code >= 500) {
                continue;
            }
            return false;
        }

        return false;
    }
}
