<?php
$accessKey = 'ADD-YOUR-API-KEY';
$perPage = 10;
$totalImages = isset($_GET['count']) ? min(intval($_GET['count']), 30) : 15;
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$orientation = isset($_GET['orientation']) && in_array($_GET['orientation'], ['portrait', 'landscape']) ? $_GET['orientation'] : '';
$images = [];
$error = '';

try {
    $url = $query
        ? "https://api.unsplash.com/search/photos?page=1&per_page=$totalImages&query=" . urlencode($query) . ($orientation ? "&orientation=$orientation" : '') . "&client_id=$accessKey"
        : "https://api.unsplash.com/photos?page=1&per_page=$totalImages" . ($orientation ? "&orientation=$orientation" : '') . "&client_id=$accessKey";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpCode !== 200) {
        throw new Exception('API request failed with status ' . $httpCode);
    }
    
    curl_close($ch);
    $data = json_decode($response, true);
    $images = $query ? ($data['results'] ?? []) : ($data ?? []);
} catch (Exception $e) {
    $error = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsplash Image Gallery</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .controls {
            max-width: 1200px;
            margin: 0 auto 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .controls input, .controls select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .controls button {
            padding: 8px 16px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .controls button:hover {
            background-color: #0056b3;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .gallery img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .pagination {
            text-align: center;
            margin: 20px 0;
        }
        .pagination button {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .pagination button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        .pagination button:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .loading, .error {
            text-align: center;
            font-size: 1.2em;
            margin: 20px;
        }
        @media (max-width: 600px) {
            .gallery {
                grid-template-columns: 1fr;
            }
            .gallery img {
                height: 150px;
            }
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="controls">
        <form method="GET" action="index.php">
            <input type="text" name="query" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search images...">
            <input type="number" name="count" min="1" max="30" value="<?php echo htmlspecialchars($totalImages); ?>" placeholder="Number of images">
            <select name="orientation" multiple>
                <option value="portrait" <?php echo in_array('portrait', explode(',', $orientation)) ? 'selected' : ''; ?>>Portrait</option>
                <option value="landscape" <?php echo in_array('landscape', explode(',', $orientation)) ? 'selected' : ''; ?>>Landscape</option>
            </select>
            <button type="submit">Search</button>
        </form>
    </div>
    <div class="gallery" id="gallery">
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <?php foreach ($images as $index => $image): ?>
                <img src="<?php echo htmlspecialchars($image['urls']['regular']); ?>" alt="<?php echo htmlspecialchars($image['alt_description'] ?? 'Unsplash Image'); ?>" class="gallery-image" style="display: <?php echo $index < $perPage ? 'block' : 'none'; ?>;">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="pagination">
        <button id="prevBtn" disabled>Previous</button>
        <span id="pageInfo"></span>
        <button id="nextBtn">Next</button>
    </div>
    <div class="loading" id="loading" style="display: none;">Loading...</div>
    <div class="error" id="error" style="display: none;"></div>

    <script>
        const perPage = <?php echo $perPage; ?>;
        let currentPage = 1;
        const totalImages = <?php echo count($images); ?>;
        const gallery = document.getElementById('gallery');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const pageInfo = document.getElementById('pageInfo');
        const images = document.querySelectorAll('.gallery-image');

        function displayImages() {
            images.forEach((img, index) => {
                const start = (currentPage - 1) * perPage;
                const end = start + perPage;
                img.style.display = index >= start && index < end ? 'block' : 'none';
            });
            updatePagination();
        }

        function updatePagination() {
            const totalPages = Math.ceil(totalImages / perPage);
            pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
        }

        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                displayImages();
            }
        });

        nextBtn.addEventListener('click', () => {
            if (currentPage < Math.ceil(totalImages / perPage)) {
                currentPage++;
                displayImages();
            }
        });

        displayImages();
    </script>
</body>
</html>
