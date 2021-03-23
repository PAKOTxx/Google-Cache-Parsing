<?php
set_time_limit(0);
error_reporting(1);
$array_of_files_in_folder = getDirContents(getcwd() . '/output_test');
$array_of_files_in_folder = delete_non_html_files($array_of_files_in_folder);
//echo '<pre>', var_dump($array_of_files_in_folder), '</pre>';
foreach ($array_of_files_in_folder as $file) {
    $article_authors = array();
    $article_h1 = '';
    $article_header = '';
    $article_date = '';
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
    }

    $spans = $doc->getElementsByTagName('span');
    foreach ($spans as $span) {
        if ($span->hasAttribute('itemprop')) {
            if ($span->getAttribute('itemprop') == 'datePublished dateModified') {
                $article_date = $span->getAttribute('content');
            }
        }
    }


    echo $file;
    echo '<br>';
    echo $article_date;
    echo '<br>';
    echo $article_header;
    echo '<br>';
    echo $article_h1;
    echo '<br>';
    var_dump($article_authors); //массив авторов статьи, если он не один то 0, 1, 2 и т.д.



    //var_dump($metas);
    // $nodeList = $xpath->query("//a[@class='tag-info']");
    // $node = $nodeList->item(0);
    // echo "<p>" . $node->nodeValue . "</p>";
}

//echo '<pre>', print_r($authors), '</pre>';








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
