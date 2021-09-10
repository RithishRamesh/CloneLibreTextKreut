<?php
namespace Overrides\IMSGlobal\LTI;

class Cache {

    private $cache;

    public function get_launch_data($key) {
        $this->load_cache();
        return $this->cache[$key];
    }

    public function cache_launch_data($key, $jwt_body) {
        $this->cache[$key] = $jwt_body;
        $this->save_cache();
        return $this;
    }

    public function cache_nonce($nonce) {
        $this->cache['nonce'][$nonce] = true;
        $this->save_cache();
        return $this;
    }

    public function check_nonce($nonce) {
        $this->load_cache();
        if (!isset($this->cache['nonce'][$nonce])) {
            return false;
        }
        return true;
    }

    private function get_dir(): string
    {
        $dir = "dir not defined";
        switch (app()->environment()){
            case('dev'):
                $dir = sys_get_temp_dir();
                break;
            case('staging'):
            case('production'):
                $dir = '/mnt/local/lti';
                break;
        }
        return $dir;
    }
    private function load_cache() {

        $cache = file_get_contents($this->get_dir() . '/lti_cache.txt');
        if (empty($cache)) {
            file_put_contents($this->get_dir() . '/lti_cache.txt', '{}');
            $this->cache = [];
        }
        $this->cache = json_decode($cache, true);
    }

    private function save_cache() {
        file_put_contents($this->get_dir()  . '/lti_cache.txt', json_encode($this->cache));
    }
}
?>
