<?php

    function crawlAndExtractData($url) {
        try {
            $html = fetchDataFromUrl($url);

            // Create a new DOMDocument
            $dom = new DOMDocument;
            libxml_use_internal_errors(true); // Enable user error handling

            // Load HTML with error handling
            if (!$dom->loadHTML($html)) {
                handleXmlErrors();
                throw new Exception("Failed to load HTML");
            }

            libxml_use_internal_errors(false); // Disable user error handling

            // Create a new DOMXPath
            $xpath = new DOMXPath($dom);

            // Define the XPath query to get all prize data within the "bkqmiennam" class
            $query = '//div[@class="box_kqxs"]//table[@class="bkqmiennam"]';
            $tables = $xpath->query($query);

            // Initialize an array to store prize data for each province
            $provincePrizes = array();

            // Loop through the tables (assuming multiple provinces)
            foreach ($tables as $table) {
                // Get the province name
                $provinceName = $xpath->query('.//td[@class="tinh"]', $table)->item(0)->nodeValue;

                // Initialize an array to store prize data for the current province
                $provinceData = extractPrizeData($xpath, $table);

                // Store the province data in the main array using the province name as the key
                $provincePrizes[$provinceName] = $provinceData;
            }

            // Output the resulting array
            return $provincePrizes;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    function fetchDataFromUrl($url, $time = 30) {
        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    
            $headers = array(
                "authority: www.minhngoc.net.vn",
                "accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
                "accept-language: en-US,en;q=0.9,vi;q=0.8",
                "sec-ch-ua-mobile: ?0",
                "sec-fetch-dest: document",
                "sec-fetch-mode: navigate",
                "sec-fetch-site: same-origin",
                "sec-fetch-user: ?1",
                "upgrade-insecure-requests: 1",
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $time - 5); 
            curl_setopt($curl, CURLOPT_TIMEOUT, $time);
    
            $html = curl_exec($curl);
    
            // Check if curl_exec was successful
            if ($html === false) {
                throw new Exception("Curl error: " . curl_error($curl));
            }
    
            // Check HTTP response code
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($httpCode !== 200) {
                throw new Exception("HTTP error: " . $httpCode);
            }
    
            curl_close($curl);
    
            return $html;
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }

    function extractPrizeData($xpath, $table) {
        // Get all prize values for "giaiSo" category within the "bkqmiennam" class
        $prizeValues = $xpath->query('.//td[starts-with(@class, "giai")]/div', $table);

        // Initialize an array to store prize data for the current province
        $provinceData = array();

        // Loop through the prize values and store them in the array
        foreach ($prizeValues as $prizeValue) {
            $prizeCategory = $prizeValue->parentNode->getAttribute('class'); // Get the class of the parent td
            $provinceData[$prizeCategory][] = $prizeValue->nodeValue;
        }

        return $provinceData;
    }

    function handleXmlErrors() {
        foreach (libxml_get_errors() as $error) {
            // Handle XML errors here, if needed
            echo $error->message;
        }
        libxml_clear_errors();
    }

    // Example usage
    $url = "https://www.minhngoc.net.vn/ket-qua-xo-so/10-01-2024.html";
    echo "<pre>";
    print_r(crawlAndExtractData($url));
