<?php
set_time_limit(0);
error_reporting(1);

//$mysqli = new mysqli('135.125.109.162', 'ubr', '9hYoGPyB', 'ubr_2016', 3306);
$mysqli = new mysqli('165.22.202.28', 'ubr', '9hYoGPyB', 'ubr_2016', 3306);
if ($mysqli->connect_error) {
    die('Ошибка подключения (' . $mysqli->connect_errno . ') '
        . $mysqli->connect_error);
} else {
    echo "connected";
    // if ($result = $mysqli->query("SELECT * FROM articles ORDER BY absnum DESC LIMIT 10")) {
    //     while ($row = $result->fetch_row()) {
    //         printf("%s (%s)\n", $row[0], $row[1]);
    //     }
    //     $result->close();
    // }
}


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
    $article_category = '';
    $article_category_id = '';
    $author_data_authors = array();
    $authors_ids_to_insert = array();
    $sources_ids_to_insert = array();
    $tags_ids_to_insert = array();
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
    $article_path_without_html = str_replace(' (2)', '', $article_path_without_html);
    $article_id = substr($article_path_without_html, -7);
    $temp_string_for_replace = substr($article_path_without_html, -8);

    preg_match_all('/(.*)\\\(.*)\\\(.*).html/', $file, $article_category_array);
    $article_category = $article_category_array[2][0];
    $article_slug = $article_category_array[3][0];
    $article_slug = str_replace($temp_string_for_replace, '', $article_slug);

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
    echo $article_id;
    echo '<br>';
    // echo $article_slug;
    // echo '<br>';
    echo $article_category;
    echo '<br>';
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
    // var_dump($article_tags); //массив
    // echo '<br>';
    // var_dump($article_tags_slugs); //массив
    // echo '<br>';
    // var_dump($article_sources); //массив
    // echo '<br>';
    // var_dump($article_sources_hrefs); //массив

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
    } else if (!$article_slug) {
        error_log_1($file, '$article_slug');
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
    if ($mysqli->connect_error) {
        die('Ошибка подключения (' . $mysqli->connect_errno . ') '
            . $mysqli->connect_error);
    } else {
        $article_slug = $mysqli->real_escape_string($article_slug);
        $article_body = $mysqli->real_escape_string($article_body);
        $article_h1 = $mysqli->real_escape_string($article_h1);
        $article_header = $mysqli->real_escape_string($article_header);
        $article_seo_descr = $mysqli->real_escape_string($article_seo_descr);
        $article_seo_title = $mysqli->real_escape_string($article_seo_title);

        if ($result = $mysqli->query("SELECT absnum FROM pages WHERE alias='" . $article_category . "' AND (type=96 OR type=48)")) {
            while ($row = $result->fetch_row()) {
                $article_category_id = $row[0];
            }

            if (!$article_authors) {
                $author_main = 2231;
            } else {
                $author_data[0] = explode(" ", $article_authors[0]);
                $sql = "SELECT absnum FROM users WHERE firstname LIKE '{$author_data[0][0]}' AND lastname LIKE '{$author_data[0][1]}'";
                if ($result = $mysqli->query($sql)) {
                    while ($row = $result->fetch_row()) {
                        $author_main = $row[0];
                    }
                } else {
                    $author_main = 2231;
                }
            }
            $sql = "INSERT INTO articles (absnum, alias, title, header, meta_title, meta_description, body, category, adate, created, changed, authors, sources,help_absnum, approved, langid, userid, user_changed, type) VALUES ('{$article_id}','{$article_slug}','{$article_h1}','{$article_header}','{$article_seo_title}','{$article_seo_descr}','{$article_body}','{$article_category_id}','{$article_date_converted}','{$article_date_converted}','{$article_date_converted}', '', '', 0, 1, 1,'{$author_main}','{$author_main}', 4)";
            if ($result = $mysqli->query($sql)) {
                if ($article_authors) {
                    foreach ($article_authors as $author_fio) {
                        $author_data_authors[] = explode(" ", $author_fio);
                    }
                    foreach ($author_data_authors as $authorr) {
                        $sql = "SELECT absnum FROM users WHERE firstname LIKE '{$authorr[0]}' AND lastname LIKE '{$authorr[1]}'";
                        if ($result = $mysqli->query($sql)) {
                            $row = $result->fetch_row();
                            $authors_ids_to_insert[] = $row[0];
                        }
                    }
                    foreach ($authors_ids_to_insert as $index => $id) {
                        $sql = "INSERT INTO articles_authors (article, absnum, position) VALUES ({$article_id}, {$id}, {$index})";
                        $mysqli->query($sql);
                    }
                }
                if ($article_sources) {
                    foreach ($article_sources as $index => $value) {
                        $name = $value;
                        //$url = $article_sources_hrefs[$index];
                        // var_dump($name);
                        // var_dump($url);
                        //$sql = "SELECT absnum FROM sources WHERE name LIKE '{$name}' AND mainurl LIKE '{$url}'";
                        $sql = "SELECT absnum FROM sources WHERE name LIKE '{$name}'";
                        if ($result = $mysqli->query($sql)) {
                            $row = $result->fetch_row();
                            $sources_ids_to_insert[] = $row[0];
                        }
                    }
                    foreach ($sources_ids_to_insert as $index => $id) {
                        $sql = "INSERT INTO articles_sources (article, absnum, position) VALUES ({$article_id}, {$id}, {$index})";
                        $mysqli->query($sql);
                    }
                }
                if ($article_tags) {
                    foreach ($article_tags as $index => $value) {
                        $name = $value;
                        //$slug = $article_tags_slugs[$index];
                        //$sql = "SELECT absnum FROM tags WHERE tag LIKE '{$name}' AND alias LIKE '{$slug}'";
                        $sql = "SELECT absnum FROM tags WHERE tag LIKE '{$name}'";
                        if ($result = $mysqli->query($sql)) {
                            $row = $result->fetch_row();
                            $tags_ids_to_insert[] = $row[0];
                        }
                    }
                    foreach ($tags_ids_to_insert as $index => $id) {
                        $sql = "INSERT INTO articles_tags (article, absnum, position) VALUES ({$article_id}, {$id}, {$index})";
                        $mysqli->query($sql);
                    }
                }
                $sql = "INSERT INTO articles_pages (article, absnum, position, adate) VALUES ({$article_id}, {$article_category_id}, 0, '{$article_date_converted}')";
                $mysqli->query($sql);
                done_log($article_id);
            } else {
                $sql = "INSERT INTO articles_pages (article, absnum, position, adate) VALUES ({$article_id}, {$article_category_id}, 0, '{$article_date_converted}')";
                $mysqli->query($sql);
                error_log_1($file, 'SQL INSERT N1 NOT WORKING ' . $mysqli->error . ' ');
                echo $mysqli->error;
            }
        } else {
            error_log_1($file, 'CANT SELECT CATEGORY ID ');
        }
        //break;
    }
}

//echo '<pre>', print_r($authors), '</pre>';


function error_log_1($file, $item)
{
    $error = gmdate("Y-m-d\TH:i:s\Z") . ' ' . $file . '  ' . $item . ' is NULL';
    error_log(print_r($error, true) . PHP_EOL, 3, getcwd() . '/errors_arts.log');
}

function done_log($id)
{
    error_log(print_r($id, true) . PHP_EOL, 3, getcwd() . '/done_arts.log');
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
