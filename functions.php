<?php
declare(strict_types=1);

/**
 * Redimensionne une image uploadée (max 640px sur le plus grand côté)
 * et l'enregistre en JPEG qualité 82 dans le dossier donné.
 * Retourne le nom de fichier généré.
 */
function handleUpload(array $file, string $uploadDir): string
{
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Le téléversement de l'image a échoué.");
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException("L'image dépasse la taille maximale (15 Mo).");
    }

    $info = getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException("Le fichier envoyé n'est pas une image valide.");
    }

    [$width, $height, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($file['tmp_name']);
            break;
        default:
            throw new RuntimeException("Format d'image non supporté (JPEG, PNG, WEBP ou GIF uniquement).");
    }
    if (!$src) {
        throw new RuntimeException("Impossible de lire l'image.");
    }

    $maxDim = 640;
    if ($width > $height && $width > $maxDim) {
        $newW = $maxDim;
        $newH = (int) round($height * ($maxDim / $width));
    } elseif ($height > $maxDim) {
        $newH = $maxDim;
        $newW = (int) round($width * ($maxDim / $height));
    } else {
        $newW = $width;
        $newH = $height;
    }

    $dst = imagecreatetruecolor($newW, $newH);
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imagedestroy($src);

    $filename = bin2hex(random_bytes(8)) . '.jpg';
    $destPath = rtrim($uploadDir, '/') . '/' . $filename;

    if (!imagejpeg($dst, $destPath, 82)) {
        imagedestroy($dst);
        throw new RuntimeException("Échec de l'enregistrement de l'image.");
    }
    imagedestroy($dst);

    return $filename;
}

function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}
