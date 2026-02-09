<?php
function saveBase64Image($base64String, $subDir = 'uploads') {
    if (empty($base64String)) return '';
    
    // If it's already a path (not base64), return it
    if (strpos($base64String, 'data:image') === false) {
        // Strip leading ../ if present to keep DB path relative to root
        if (strpos($base64String, '../') === 0) {
            return substr($base64String, 3);
        }
        return $base64String;
    }

    // Create directory if not exists
    $uploadDir = __DIR__ . '/' . $subDir . '/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Extract data and extension
    preg_match('/data:image\/(.*?);base64,(.*)/', $base64String, $matches);
    if (count($matches) !== 3) return $base64String;

    $extension = $matches[1];
    $data = base64_decode($matches[2]);
    
    // Generate unique filename
    $filename = uniqid('img_', true) . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Save file
    if (file_put_contents($filePath, $data)) {
        return $subDir . '/' . $filename;
    }
    
    return $base64String;
}

function processItemsImages($itemsJson, $subDir = 'uploads/items') {
    if (empty($itemsJson)) return '[]';
    
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) return $itemsJson;
    
    foreach ($items as &$item) {
        if (!empty($item['image'])) {
            $item['image'] = saveBase64Image($item['image'], $subDir);
        }
    }
    
    return json_encode($items, JSON_UNESCAPED_UNICODE);
}

function getFullPath($path) {
    if (empty($path)) return '';
    if (strpos($path, 'data:image') === 0) return $path;
    if (strpos($path, 'http') === 0) return $path;
    // Only prepend ../ if it's not already there
    if (strpos($path, '../') === 0) return $path;
    return '../' . $path;
}

function processItemsPaths(&$items) {
    if (empty($items)) return;
    if (is_string($items)) {
        $items_arr = json_decode($items, true);
        if (is_array($items_arr)) {
            foreach ($items_arr as &$item) {
                if (!empty($item['image'])) {
                    $item['image'] = getFullPath($item['image']);
                }
            }
            $items = json_encode($items_arr, JSON_UNESCAPED_UNICODE);
        }
    } elseif (is_array($items)) {
        foreach ($items as &$item) {
            if (!empty($item['image'])) {
                $item['image'] = getFullPath($item['image']);
            }
        }
    }
}
?>
