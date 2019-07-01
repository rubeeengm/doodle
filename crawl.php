<?php
    include("classes/DomDocumentParser.php");
    include("config.php");

    //enlaces que ya analizamos
    $alreadyCrawled = array();
    //enlaces que nos falta analizar
    $crawling = array();
    //imagenes que ya encontramos
    $alreadyFoundImages = array();
    //verifica si el link existe
    function linkExists($url) {
        //para poder utilizar la variable con que se encuentra en config.php
        global $con;
        //recupera todas las ṕáginas que conincidan con la url
        $query = $con->prepare(
            "SELECT * FROM sites WHERE url = :url"
        );
        //enlaza la varibale $url con el holder que se encuentra en la consulta
        $query->bindParam(":url", $url);
        //ejecuta la consulta
        $query->execute();

        //cunta el numero de filas que devolvió la consulto y regresa true si es igual con 0
        return $query->rowCount() != 0;
    }
    //insertar links en la base de datos
    function insertLink($url, $title, $description, $keywords) {
        //para poder utilizar la variable con que se encuentra en config.php
        global $con;
        //consulta que guarda los links en la base de datos
        $query = $con->prepare(
            "INSERT INTO sites(url, title, description, keywords) VALUES (:url, :title, :description, :keywords)"
        );
        //enlaza las varibales con los holder que se encuentra en la consulta
        $query->bindParam(":url", $url);
        $query->bindParam(":title", $title);
        $query->bindParam(":description", $description);
        $query->bindParam(":keywords", $keywords);
        //ejectura la consulta, devuelve falso si falla
        return $query->execute();
    }
    //insertar links de imagenes en la base de datos
    function insertImage($url, $src, $alt, $title) {
        //para poder utilizar la variable con que se encuentra en config.php
        global $con;
        //consulta que guarda imagenes en la base de datos
        $query = $con->prepare(
            "INSERT INTO images(siteUrl, imageUrl, alt, title) VALUES (:siteUrl, :imageUrl, :alt, :title)"
        );
        //enlaza las varibales con los holder que se encuentra en la consulta
        $query->bindParam(":siteUrl", $url);
        $query->bindParam(":imageUrl", $src);
        $query->bindParam(":alt", $alt);
        $query->bindParam(":title", $title);
        //ejecuta la consulta
        $query->execute();
    }
    //transforma enlaces relativos a absolutos
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
    //obtiene la información  de una url
    function getDetails($url) {
        global $alreadyFoundImages;
        //dom de la página
        $parser = new DomDocumentParser($url);
        //titulo de la página
        $titleArray = $parser->getTitleTags();
        //si no tiene titulo ignora la url
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

        //limpia las cadenas
        $description = str_replace("\n", "", $description);
        $keywords = str_replace("\n", "", $keywords);

        //echo "URL: $url, Title: $title, Description: $description, keywords: $keywords<br>";
        //verifica si existe el link
        if (linkExists($url)) {
            echo "$url already exists<br>";
        } else if (insertLink($url, $title, $description, $keywords)) { //inserta el link en la base de datos
            echo "SUCCESS: $url<br>";
        } else {
            echo "ERROR: Failed to insert $url<br>";
        }

        //obtiene todas la imagenes de la página web
        $imageArray = $parser->getImages();

        foreach($imageArray as $image) {
            $src = $image->getAttribute("src");
            $alt = $image->getAttribute("alt");
            $title = $image->getAttribute("title");

            //si no contienen esos atributos los ignora
            if (!$title && !$alt) {
                continue;
            }

            //transforma el enlace relativo a absoluto
            $src = createLink($src, $url);

            //si no ha sido encontrada la imagen la agrega al arreglo
            if (!in_array($src, $alreadyFoundImages)) {
                $alreadyFoundImages[] = $src;

                insertImage($url, $src, $alt, $title);
            }
        }
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
            }
        }

        //saca el primer elemento del array y lo retorna
        array_shift($crawling);
        //analiza todos los links de las páginas que nos faltan
        foreach($crawling as $site) {
            followLinks($site);
        }
    }

    $startUrl = "http://www.proday.mx";
    followLinks($startUrl);
?>