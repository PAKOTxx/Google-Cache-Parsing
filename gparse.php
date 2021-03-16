<?php
$GLOBALS['urls_array'];
$GLOBALS['proxy_list_array'];
$GLOBALS['proxy_list_index'] = 0;
$GLOBALS['proxy_list_file'] = 'proxy.txt';
$GLOBALS['proxy_enabled'] = false;

function gparse($host, $file)
{
    generate_urls_array($file);
    foreach ($GLOBALS['urls_array'] as $url) {
        $content = get_page_content($host, $url);
        if ($content) {
            $save_on = getSavePath($url);
            if (prepareFilePath($save_on)) {
                if (!file_put_contents($save_on, $content)) {
                    echo 'error on file put contents';
                }
            }
        }
        sleep(rand(10, 40)); //timeout
    }
}

function generate_proxy_list()
{
    if (is_file($GLOBALS['proxy_list_file'])) {
        $input = file_get_contents($GLOBALS['proxy_list_file']);
        if ($input) {
            $GLOBALS['proxy_list_array'] = array_filter(array_map('trim', explode("\n", $input)));
            if (empty($GLOBALS['proxy_list_array'])) {
                echo 'proxy_array empty';
                die;
            }
        }
    } else {
        echo 'no proxy file';
        die;
    }
}

function generate_urls_array($file)
{
    if (is_file($file)) {
        $input = file_get_contents($file);
        if ($input) {
            $GLOBALS['urls_array'] = array_filter(array_map('trim', explode("\n", $input)));
            if (empty($GLOBALS['urls_array'])) {
                echo 'urls_array empty';
                die;
            }
        }
    } else {
        echo 'no urls file';
        die;
    }
}

function get_page_content($host, $url)
{
    $fullurl = 'https://webcache.googleusercontent.com/search?q=cache:' . $host . $url;
    $ch = curl_init();

    if ($GLOBALS['proxy_enabled'] == true) {
        curl_setopt($ch, CURLOPT_PROXY, $GLOBALS['proxy_list_array'][$GLOBALS['proxy_list_index']]);
        curl_setopt($ch, CURLOPT_PROXYUSERPWD, "vestisite:X5a8YsK");
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL, $fullurl);
    curl_setopt($ch, CURLOPT_USERAGENT, getUserAgent());
    $content = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($status_code == 302) {
        echo 'need to change proxy';
        $GLOBALS['proxy_list_index']++;
        if ($GLOBALS['proxy_list_index'] == count($GLOBALS['proxy_list_array'])) {
            echo 'all proxy banned for today';
            die;
        } else {
            get_page_content($host, $url);
        }
    } else if ($status_code == 404) {
        echo 'no such article in cache';
    } else {
        return $content;
    }

    curl_close($ch);
}

function getUserAgent()
{

    // https://techblog.willshouse.com/2012/01/03/most-common-user-agents/
    $common_ua = [
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0',
        'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:43.0) Gecko/20100101 Firefox/43.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:43.0) Gecko/20100101 Firefox/43.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.106 Safari/537.36',
        'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.7 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.7',
        'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.80 Safari/537.36',
        'Mozilla/5.0 (iPad; CPU OS 9_2 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13C75 Safari/601.1',
        'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0',
        'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
    ];
    return array_rand($common_ua);
}

function getSavePath($url_without_base)
{
    $save_path = getcwd() . '/output';
    $isempty = trim($url_without_base, '/');
    if (empty($isempty) || $url_without_base === $save_path) {
        return $save_path . '/index.html';
    } else {
        if (!(strpos($url_without_base, '.html') !== false ||
            strpos($url_without_base, '.htm') !== false ||
            strpos($url_without_base, '.php') !== false)) {
            return $save_path . $url_without_base . '.html';
        } else {
            return $save_path . $url_without_base;
        }
    }
}

function prepareFilePath($file_path)
{
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            echo 'cannot create folder / file';
            return false;
        }
    }
    return true;
}

generate_proxy_list();
gparse('https://ubr.ua', 'urls_test.txt');
