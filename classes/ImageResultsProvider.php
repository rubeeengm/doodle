<?php
    class ImageResultsProvider{
        private $con;

        public function __construct($con) {
            $this->con = $con;
        }

        public function getNumResults($term) {
            $query = $this->con->prepare(
                "SELECT COUNT(*) as total
                FROM images
                WHERE (title LIKE :term
                OR alt LIKE :term)
                AND broken = 0
                "
            );

            $searchTerm = "%".$term."%";
            $query->bindParam(":term", $searchTerm);
            $query->execute();

            $row = $query->fetch(PDO::FETCH_ASSOC);

            return $row["total"];
        }

        public function getResultsHtml($page, $pageSize, $term) {
            $fromLimit = ($page -1) * $pageSize;
            // page 1 : (1-1) * 20 = 0
            // page 2 : (2-1) * 20 = 20
            // page 3 : (3-1) * 20 = 40
            $query = $this->con->prepare(
                "SELECT *
                FROM images
                WHERE (title LIKE :term
                OR alt LIKE :term)
                AND broken = 0
                ORDER BY clicks DESC
                LIMIT :fromLimit, :pageSize"
            );

            $searchTerm = "%".$term."%";
            $query->bindParam(":term", $searchTerm);
            //especifica el tipo de dato del parametro
            $query->bindParam(":fromLimit", $fromLimit, PDO::PARAM_INT);
            $query->bindParam(":pageSize", $pageSize, PDO::PARAM_INT);
            $query->execute();

            $resultsHtml = "<div class='imageResults'>";

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $id = $row["id"];
                $imageUrl = $row["imageUrl"];
                $siteUrl = $row["siteUrl"];
                $title = $row["title"];
                $alt = $row["alt"];

                if ($title) {
                    $displayText = $title;
                } else if ($alt) {
                    $displayText = $alt;
                } else {
                    $displayText = $imageUrl;
                }

                $resultsHtml .= "<div class='gridItem'>
                                    <a href='$imageUrl'>
                                        <img src='$imageUrl'>
                                        <span class='details'>$displayText</span>
                                    </a>
                                </div>";
            }

            $resultsHtml .= "</div>";

            return $resultsHtml;
        }
    }
?>