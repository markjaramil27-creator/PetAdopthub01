<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect to login if not authenticated
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect to home if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

// Get user information
function getUserInfo($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get pet information
function getPetInfo($pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM pets WHERE id = ?");
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Check if pet is in user's favorites
function isFavorite($user_id, $pet_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND pet_id = ?");
    $stmt->bind_param("ii", $user_id, $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Get available pets
function getAvailablePets($limit = 6) {
    global $conn;
    if ($limit === null) {
        // Get all pets without limit
        $stmt = $conn->prepare("SELECT * FROM pets WHERE status = 'Available' ORDER BY created_at DESC");
    } else {
        // Get limited pets
        $stmt = $conn->prepare("SELECT * FROM pets WHERE status = 'Available' ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Normalize an image path stored in the database and convert it to a URL that will
// work regardless of which hostname or machine the client is using.  The previous
// implementation built a fully qualified URL using SITE_URL or the current request
// host, which caused images to point to "localhost" when the site was accessed from
// another computer.  Browsers on a different device then attempted to fetch the
// image from their own local machine and naturally failed.
// The application is deployed in a possible subdirectory (e.g. /petadopthub), so we
// also need to ensure the returned path includes that base segment.  The easiest way
// is to derive the path portion of SITE_URL and prepend it.
function normalizePetImagePath($path, $default = DEFAULT_PET_IMAGE_URL) {
    // nothing provided -> default (already a usable URL or relative path)
    if (empty($path)) {
        return $default;
    }

    $path = trim($path);

    // If stored as a full URL, convert local development hosts to current host.
    if (preg_match('#^https?://#i', $path)) {
        $parsedUrl = parse_url($path);
        $imageHost = strtolower($parsedUrl['host'] ?? '');

        $localHosts = array_filter(array_map('strtolower', [
            'localhost',
            '127.0.0.1',
            '::1',
            $_SERVER['HTTP_HOST'] ?? '',
            $_SERVER['SERVER_NAME'] ?? '',
            gethostname(),
        ]));

        // Keep external URLs as-is (e.g. CDN or 3rd-party images).
        if (!in_array($imageHost, $localHosts, true)) {
            return $path;
        }

        // Rewrite local host URLs to server host so any device can access the same server image.
        $path = ($parsedUrl['path'] ?? '')
            . (!empty($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '')
            . (!empty($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '');

        if ($path === '') {
            return $default;
        }
    }

    // convert backslashes to forward slashes (windows paths) and trim whitespace
    $p = str_replace('\\', '/', trim($path));

    // strip leading drive letter if present (e.g. C:/xampp/htdocs/...)
    $p = preg_replace('#^[a-zA-Z]:/#', '', $p);

    // remove any leading slashes so we can prepend exactly one later
    $p = ltrim($p, '/');

    // pet images are saved under DOCUMENT_ROOT/uploads/pets, which is outside the
    // application subdirectory.  In other words, URLs should always be rooted at the
    // web server's document root, not beneath the project folder.  We therefore ignore
    // any application base path and simply construct a root-relative path.

    // build relative path from root
    $relative = '/' . $p;

    // construct a full URL using the host/scheme from current request so that cases
    // where the page is reached via IP, host alias or port remain consistent.
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host   = $_SERVER['HTTP_HOST'];

    return $scheme . $host . $relative;
}

// Extract the first image from a pet record (handles JSON array or single value)
function getFirstPetImage($pet, $default = DEFAULT_PET_IMAGE_URL) {
    if (empty($default)) {
        $default = DEFAULT_PET_IMAGE_URL;
    }

    $img = $default;
    if (!empty($pet['image'])) {
        $decoded = json_decode($pet['image'], true);
        if (is_array($decoded) && count($decoded) > 0) {
            $img = $decoded[0];
        } else {
            $img = $pet['image'];
        }
    }
    return normalizePetImagePath($img, $default);
}

// Search pets
function searchPets($keyword = '', $type = '', $age = '') {
    global $conn;
    $sql = "SELECT * FROM pets WHERE status = 'Available'";
    $params = [];
    $types = '';
    
    if (!empty($keyword)) {
        $sql .= " AND (name LIKE ? OR breed LIKE ? OR description LIKE ?)";
        $keyword_param = "%$keyword%";
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $params[] = $keyword_param;
        $types .= 'sss';
    }
    
    if (!empty($type)) {
        $sql .= " AND type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    if (!empty($age)) {
        $sql .= " AND age <= ?";
        $params[] = $age;
        $types .= 'i';
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>