<?php
    include("classes/DomDocumentParser.php");
    //enlaces que ya analizamos
    $alreadyCrawled = array();
    //enlaces que nos falta analizar
    $crawling = array();

    function createLink($src, $url) {
        $scheme = parse_url($url)["scheme"]; // http
        $host = parse_url($url)["host"]; // www.reecekenney.com

        if (substr($src,0,2) == "//") {
            $src = $scheme.":".$src;
        } else if (substr($src,0,1) == "/") {
            $src = $scheme."://".$host.$src;
        } else if (substr($src,0,2) == "./") {
            $src = $scheme."://".$host.dirname(parse_url($url)["path"]).substr($src,1);
        } else if (substr($src,0,3) == "../") {
            $src = $scheme."://".$host."/".$src;
        } else if (substr($src,0,5) != "https" && substr($src,0,4) != "http") {
            $src = $scheme."://".$host."/".$src;
        }

        return $src;
    }

    function getDetails($url) {
        $parser = new DomDocumentParser($url);
        $titleArray = $parser->getTitleTags();

        if (sizeof($titleArray) == 0 || $titleArray->item(0) == NULL) {
            return;
        }

        $title = $titleArray->item(0)->nodeValue;
        $title = str_replace("\n","",$title);

        if ($title == "") {
            return;
        }

        $description = "";
        $keywords = "";

        $metasArray = $parser->getMetaTags();

        foreach ($metasArray as $meta) {
            if ($meta->getAttribute("name") == "description") {
                $description = $meta->getAttribute("content");
            }

            if ($meta->getAttribute("name") == "keywords") {
                $keywords = $meta->getAttribute("content");
            }
        }

        $description = str_replace("\n", "", $description);
        $keywords = str_replace("\n", "", $keywords);

        echo "URL: $url, Title: $title, Description: $description, keywords: $keywords<br>";
    }

    //recuperad todos los enlaces de una página web de forma recursiva
    function followLinks($url) {
        global $alreadyCrawled;
        global $crawling;
        //recupera el dom de una página web
        $parser = new DomDocumentParser($url);
        //obtiene todos los enlaces de la página web
        $linkList = $parser->getLinks();

        foreach($linkList as $link) {
            //selecciona el enlace y recupera la dirección
            $href = $link->getAttribute("href");
            //ignora los #
            if (strpos($href, "#") !== false) {
                continue;
            } else if (substr($href, 0, 11) == "javascript:") { //ignora enlaces js
                continue;
            }
            //convierte enlaces relativos a absolutos
            $href = createLink($href, $url);
            //verifica si no se encuentra el enlace dentro de alreadyCrawled, para no ignorarlo
            if (!in_array($href, $alreadyCrawled)) {
                $alreadyCrawled[] = $href;
                $crawling[] = $href;

                getDetails($href);
            } else return;
        }

        //saca el primer elemento del array y lo retorna
        array_shift($crawling);
        //analiza todos los links de las páginas que nos faltan
        foreach($crawling as $site) {
            followLinks($site);
        }
    }

    $startUrl = "http://www.bbc.com";
    followLinks($startUrl);
?>