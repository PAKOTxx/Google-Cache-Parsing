<?php
set_time_limit(0);
error_reporting(1);

// $mysqli = new mysqli('192.168.50.16', 'root', '@Dven2re', 'ubr_2016', 3306);
// if ($mysqli->connect_error) {
//     die('Ошибка подключения (' . $mysqli->connect_errno . ') '
//         . $mysqli->connect_error);
// } else {
//     echo "connected";
//     if ($result = $mysqli->query("SELECT * FROM articles ORDER BY absnum DESC LIMIT 10")) {
//         while ($row = $result->fetch_row()) {
//             printf("%s (%s)\n", $row[0], $row[1]);
//         }
//         $result->close();
//     }
// }


$array_of_files_in_folder = getDirContents(getcwd() . '/output');
$array_of_files_in_folder = delete_non_html_files($array_of_files_in_folder);
//echo '<pre>', var_dump($array_of_files_in_folder), '</pre>';
foreach ($array_of_files_in_folder as $file) {
    $article_authors = array();
    $article_h1 = '';
    $article_header = '';
    $article_date = '';
    $article_seo_descr = '';
    $article_seo_title = '';
    $article_tags = array();
    $article_tags_slugs = array();
    $article_sources = array();
    $article_sources_hrefs = array();
    $article_date_converted = '';
    $article_body = '';
    echo '<br>----<br>';
    $doc = new DOMDocument();
    $doc->loadHTMLFile($file);
    $doc->saveHTML();
    //$xpath = new DomXPath($doc);
    $metas = $doc->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        /**
         * автора
         */
        if ($meta->hasAttribute('itemprop') && $meta->hasAttribute('content')) {
            if ($meta->getAttribute('itemprop') == 'name' && $meta->getAttribute('content') != 'UBR') {
                $article_authors[] = $meta->getAttribute('content');
                //echo $meta->getAttribute('content') . '<br>';
            }
        }
        /**
         * 
         */

        /**
         * h1 заголовок
         */
        if ($meta->hasAttribute('property') && $meta->hasAttribute('content')) {
            if ($meta->getAttribute('property') == 'og:title') {
                $article_h1 = $meta->getAttribute('content');
                //echo $meta->getAttribute('content') . '<br>';
            }
        }
        /**
         * 
         */
        /**
         * подзаголовок
         */
        if ($meta->hasAttribute('property') && $meta->hasAttribute('content')) {
            if ($meta->getAttribute('property') == 'og:description') {
                $article_header = $meta->getAttribute('content');
                //echo $meta->getAttribute('content') . '<br>';
            }
        }
        /**
         * 
         */
        /**
         * seo meta descrptiom
         */
        if ($meta->hasAttribute('name') && $meta->hasAttribute('content')) {
            if ($meta->getAttribute('name') == 'description') {
                $article_seo_descr = $meta->getAttribute('content');
                //echo $meta->getAttribute('content') . '<br>';
            }
        }
        /**
         * 
         */
    }

    $spans = $doc->getElementsByTagName('span');
    foreach ($spans as $span) {
        if ($span->hasAttribute('itemprop')) {
            if ($span->getAttribute('itemprop') == 'datePublished dateModified') {
                $article_date = $span->getAttribute('content');
            }
        }
    }

    $titles = $doc->getElementsByTagName('title');
    foreach ($titles as $title) {
        $article_seo_title = $title->nodeValue;
        $article_seo_title = str_replace(' | ubr.ua', '', $article_seo_title);
    }

    $divs = $doc->getElementsByTagName('div');
    foreach ($divs as $div) {
        if ($div->hasAttribute('class')) {
            /**
             * tags and sources
             */
            if ($div->getAttribute('class') == 'tag-main-article') {
                $a_s = $div->getElementsByTagName('a');
                foreach ($a_s as $a) {
                    if ($a->hasAttribute('rel')) {
                        $article_sources[] = $a->nodeValue;
                        $article_sources_hrefs[] = preg_replace('/(.*)web\/(.*)\//Uis', '', $a->getAttribute('href'));
                    } else {
                        $article_tags[] = $a->nodeValue;
                        $article_tags_slugs[] = urldecode(preg_replace('/(.*)\/tag\//', '', $a->getAttribute('href')));
                    }
                }
            }
            /**
             * 
             */
            /**
             * дата в старой верстке
             */
            if (!$article_date) {
                if ($div->getAttribute('class') == 'news-body') {
                    $spans_in_div = $div->getElementsByTagName('span');
                    foreach ($spans_in_div as $maby_date) {
                        if ($maby_date->getAttribute('class') == 'smooth') {
                            $article_date = $maby_date->nodeValue;
                        }
                    }
                }
            }
            /**
             * 
             */
        }
        /**
         * контент статьи
         */
        if ($div->hasAttribute('data-io-article-url')) {
            $children  = $div->childNodes;
            foreach ($children as $child) {
                //var_dump($child);
                if ($child->tagName == 'div' || $child->tagName == 'script') {
                } else {
                    $paragraph = $div->ownerDocument->saveHTML($child);
                    $paragraph = preg_replace('/<img(.*)>/', '', $paragraph);
                    $paragraph = preg_replace('/http:\/\/web.archive.org\/web\/(.*)\//Uis', '', $paragraph);
                    $article_body .= $paragraph;
                }
            }
        }
        /**
         * 
         */
    }

    $article_path_without_html = str_replace('.html', '', $file);
    $article_id = substr($article_path_without_html, -7);

    preg_match_all('/(.*)\\\(.*)\\\(.*).html/', $file, $article_category_array);
    $article_category = $article_category_array[2][0];

    $article_date_converted = strtotime($article_date);
    if (!$article_date_converted) {
        $article_date_fixing = $article_date;
        $article_date_fixing = str_replace('января', 'January', $article_date_fixing);
        $article_date_fixing = str_replace('февраля', 'February', $article_date_fixing);
        $article_date_fixing = str_replace('марта', 'March', $article_date_fixing);
        $article_date_fixing = str_replace('апреля', 'April', $article_date_fixing);
        $article_date_fixing = str_replace('мая', 'May', $article_date_fixing);
        $article_date_fixing = str_replace('июня', 'June', $article_date_fixing);
        $article_date_fixing = str_replace('июля', 'July', $article_date_fixing);
        $article_date_fixing = str_replace('августа', 'August', $article_date_fixing);
        $article_date_fixing = str_replace('сентября', 'September', $article_date_fixing);
        $article_date_fixing = str_replace('октября', 'October', $article_date_fixing);
        $article_date_fixing = str_replace('ноября', 'November', $article_date_fixing);
        $article_date_fixing = str_replace('декабря', 'December', $article_date_fixing);

        $article_date_fixing = str_replace('Понедельник', 'Monday', $article_date_fixing);
        $article_date_fixing = str_replace('Вторник', 'Tuesday', $article_date_fixing);
        $article_date_fixing = str_replace('Среда', 'Wednesday', $article_date_fixing);
        $article_date_fixing = str_replace('Четверг', 'Thursday', $article_date_fixing);
        $article_date_fixing = str_replace('Пятница', 'Friday', $article_date_fixing);
        $article_date_fixing = str_replace('Суббота', 'Saturday', $article_date_fixing);
        $article_date_fixing = str_replace('Воскресенье', 'Sunday', $article_date_fixing);

        $article_date_converted = strtotime($article_date_fixing);
    }

    // echo $file;
    // echo '<br>';
    // echo $article_id;
    // echo '<br>';
    // echo $article_category;
    // echo '<br>';
    // echo $article_body;
    // echo '<br>';
    // echo $article_seo_descr;
    // echo '<br>';
    // echo $article_seo_title;
    // echo '<br>';
    // echo $article_date;
    // echo '<br>';
    // echo $article_date_converted;
    // echo '<br>';
    // echo $article_header;
    // echo '<br>';
    // echo $article_h1;
    // echo '<br>';
    // var_dump($article_authors); //массив авторов статьи, если он не один то 0, 1, 2 и т.д.
    // echo '<br>';
    // var_dump($article_tags);
    // echo '<br>';
    // var_dump($article_tags_slugs);
    // echo '<br>';
    // var_dump($article_sources);
    // echo '<br>';
    // var_dump($article_sources_hrefs);

    if (!$article_id) {
        error_log_1($file, '$article_id');
    } else if (!$article_category) {
        error_log_1($file, '$article_category');
    } else if (!$article_seo_descr) {
        error_log_1($file, '$article_seo_descr');
    } else if (!$article_seo_title) {
        error_log_1($file, '$article_seo_title');
    } else if (!$article_date_converted) {
        error_log_1($file, '$article_date_converted');
    } else if (!$article_h1) {
        error_log_1($file, '$article_h1');
    } else if (!$article_body) {
        error_log_1($file, '$article_body');
    }
    // if (!$article_header) {
    //     error_log_1($file, '$article_header');
    // }
    // if (!$article_authors) {
    //     error_log_1($file, '$article_authors');
    // }
    // if (!$article_tags) {
    //     error_log_1($file, '$article_tags');
    // }
    // if (!$article_sources) {
    //     error_log_1($file, '$article_sources');
    // }

    //var_dump($metas);
    // $nodeList = $xpath->query("//a[@class='tag-info']");
    // $node = $nodeList->item(0);
    // echo "<p>" . $node->nodeValue . "</p>";
}

//echo '<pre>', print_r($authors), '</pre>';


function error_log_1($file, $item)
{
    $error = gmdate("Y-m-d\TH:i:s\Z") . ' ' . $file . '  ' . $item . ' is NULL';
    error_log(print_r($error, true) . PHP_EOL, 3, getcwd() . '/errors_arts.log');
}


function delete_non_html_files($array)
{
    foreach ($array as $item) {
        if (strpos($item, '.html')) {
            $results[] = $item;
        }
    }
    return $results;
}



function getDirContents($dir, &$results = array())
{
    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != "..") {
            getDirContents($path, $results);
            $results[] = $path;
        }
    }

    return $results;
}
