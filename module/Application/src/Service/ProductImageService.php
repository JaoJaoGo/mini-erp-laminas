<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Http\PhpEnvironment\Request;
use RuntimeException;

/**
 * Serviço responsável pelo upload e exclusão de imagens de produto.
 *
 * O ProductImageService processa o envio de arquivos enviados via request, valida
 * extensão, tipo MIME e tamanho, e remove imagens antigas do diretório público.
 *
 * Métodos disponíveis:
 * - uploadFromRequest(Request $request, ?string $currentImagePath = null): ?string
 * - delete(?string $imagePath): void
 */
class ProductImageService
{
    public function __construct(
        private readonly string $publicDirectory,
    ) { }

    public function uploadFromRequest(Request $request, ?string $currentImagePath = null): ?string
    {
        $files = $request->getFiles()->toArray();
        $image = $files['image'] ?? null;

        if (!is_array($image) || ($image['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $currentImagePath;
        }
        
        if (($image['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha ao enviar a imagem do produto.');
        }

        $tmpName = (string) ($image['tmp_name'] ?? '');
        $originalName = (string) ($image['name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Arquivo de imagem inválido.');
        }

        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Formato de imagem inválido. Envie JPG, JPEG, PNG ou WEBP.');
        }

        $mimeType = mime_content_type($tmpName) ?: '';
        $allowedMimeTypes = [
            'image/jpeg',            
            'image/png',
            'image/webp',
        ];

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
        }

        $maxSize = 5 * 1024 * 1024;
        $fileSize = (int) ($image['size'] ?? 0);

        if ($fileSize <= 0 || $fileSize > $maxSize) {
            throw new RuntimeException('A imagem deve ter no máximo 5MB.');
        }

        $relativeDirectory = '/uploads/products';
        $targetDirectory = rtrim($this->publicDirectory, DIRECTORY_SEPARATOR) . $relativeDirectory;

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException('Não foi possível criar o diretório de upload das imagens');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Não foi possível salvar a imagem enviada.');
        }

        return $relativeDirectory . '/' . $fileName;
    }

    public function delete(?string $imagePath): void
    {
        if ($imagePath === null || trim($imagePath) === '') {
            return;
        }

        $fullPath = rtrim($this->publicDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($imagePath, '/');

        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}