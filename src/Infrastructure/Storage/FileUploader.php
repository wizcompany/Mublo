<?php
namespace Mublo\Infrastructure\Storage;

use Mublo\Core\Context\Context;

/**
 * FileUploader
 *
 * 파일 업로드 인프라 클래스
 * - 멀티 도메인 지원 (D{domain_id} 폴더 구조)
 * - 파일 저장/삭제/이동
 * - 확장자/크기 검증
 *
 * 저장 구조:
 * public/storage/D{domain_id}/{subdirectory}/{year}/{month}/{stored_name}
 *
 * @see .claude/skills/storage-path-rules.md
 */
class FileUploader
{
    private string $basePath;
    private array $defaultAllowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'zip', 'rar', '7z',
    ];

    // 보안상 절대 허용하지 않는 확장자
    private array $dangerousExtensions = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'exe', 'sh', 'bat', 'cmd', 'com', 'scr', 'pif',
        'js', 'vbs', 'wsf', 'asp', 'aspx', 'jsp', 'cgi', 'pl',
        'htaccess', 'htpasswd',
    ];

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (defined('MUBLO_PUBLIC_STORAGE_PATH') ? MUBLO_PUBLIC_STORAGE_PATH : 'public/storage');
    }

    /**
     * 파일 업로드
     *
     * @param UploadedFile $file 업로드된 파일
     * @param int $domainId 도메인 ID
     * @param array $options 옵션 [
     *   'allowed_extensions' => [],  // 허용 확장자 (비어있으면 기본값 사용)
     *   'max_size' => 10485760,      // 최대 크기 (bytes, 기본 10MB)
     *   'subdirectory' => '',        // 추가 하위 디렉토리 (예: 'board', 'avatar')
     *   'shared' => false,           // true이면 D{domainId}/ 접두사 없이 패키지 공유 경로 사용
     * ]
     * @return UploadResult
     */
    public function upload(UploadedFile $file, int $domainId, array $options = []): UploadResult
    {
        // 기본 옵션
        $allowedExtensions = $options['allowed_extensions'] ?? $this->defaultAllowedExtensions;
        $maxSize = $options['max_size'] ?? 10 * 1024 * 1024; // 10MB
        $subdirectory = $options['subdirectory'] ?? '';

        // 파일 유효성 검사
        if (!$file->isValid()) {
            return UploadResult::failure($file->getErrorMessage());
        }

        // 확장자 검사
        $extension = $file->getExtension();
        if (!$this->isExtensionAllowed($extension, $allowedExtensions)) {
            return UploadResult::failure("허용되지 않은 파일 형식입니다: {$extension}");
        }

        // 위험한 확장자 차단
        if ($this->isDangerousExtension($extension)) {
            return UploadResult::failure('보안상 허용되지 않는 파일 형식입니다.');
        }

        // 크기 검사
        if ($file->getSize() > $maxSize) {
            $maxMb = round($maxSize / 1024 / 1024, 1);
            return UploadResult::failure("파일 크기가 {$maxMb}MB를 초과했습니다.");
        }

        // 저장 경로 생성
        $shared = $options['shared'] ?? false;
        if ($shared) {
            // 패키지 공유: {subdirectory}/{year}/{month} (도메인 접두사 없음)
            $relativePath = trim($subdirectory, '/');
        } else {
            // 도메인 격리: D{domain_id}/{subdirectory}/{year}/{month}
            $relativePath = 'D' . $domainId;
            if ($subdirectory) {
                $relativePath .= '/' . trim($subdirectory, '/');
            }
        }
        if ($options['include_date'] ?? true) {
            $relativePath .= '/' . date('Y/m');
        }

        $fullDir = $this->basePath . '/' . $relativePath;
        if (!$this->ensureDirectory($fullDir)) {
            return UploadResult::failure('업로드 디렉토리를 생성할 수 없습니다.');
        }

        // 저장 파일명 생성 (해시 기반)
        $storedName = $this->generateStoredName($file->getName(), $extension);
        $fullPath = $fullDir . '/' . $storedName;

        // 파일 이동
        if (!move_uploaded_file($file->getTmpName(), $fullPath)) {
            return UploadResult::failure('파일 저장에 실패했습니다.');
        }

        // 이미지 정보 추출
        $imageWidth = null;
        $imageHeight = null;
        if ($file->isImage()) {
            $imageInfo = $file->getImageInfo();
            if ($imageInfo) {
                $imageWidth = $imageInfo['width'];
                $imageHeight = $imageInfo['height'];
            }
        }

        return UploadResult::success([
            'stored_name' => $storedName,
            'relative_path' => $relativePath,
            'full_path' => $fullPath,
            'original_name' => $file->getName(),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'image_width' => $imageWidth,
            'image_height' => $imageHeight,
        ]);
    }

    /**
     * 파일 삭제
     *
     * @param string $relativePath 상대 경로 (D1/2024/01)
     * @param string $storedName 저장된 파일명
     * @return bool
     */
    public function delete(string $relativePath, string $storedName): bool
    {
        $fullPath = $this->basePath . '/' . $relativePath . '/' . $storedName;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return true; // 이미 없으면 성공으로 처리
    }

    /**
     * 전체 경로로 파일 삭제
     */
    public function deleteByFullPath(string $fullPath): bool
    {
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return true;
    }

    /**
     * 파일 이동
     */
    public function move(string $fromPath, string $toPath): bool
    {
        if (!file_exists($fromPath)) {
            return false;
        }

        $toDir = dirname($toPath);
        if (!$this->ensureDirectory($toDir)) {
            return false;
        }

        return rename($fromPath, $toPath);
    }

    /**
     * 파일 복사
     */
    public function copy(string $fromPath, string $toPath): bool
    {
        if (!file_exists($fromPath)) {
            return false;
        }

        $toDir = dirname($toPath);
        if (!$this->ensureDirectory($toDir)) {
            return false;
        }

        return copy($fromPath, $toPath);
    }

    /**
     * 파일 존재 확인
     */
    public function exists(string $relativePath, string $storedName): bool
    {
        $fullPath = $this->basePath . '/' . $relativePath . '/' . $storedName;
        return file_exists($fullPath);
    }

    /**
     * 전체 경로 반환
     */
    public function getFullPath(string $relativePath, string $storedName): string
    {
        return $this->basePath . '/' . $relativePath . '/' . $storedName;
    }

    /**
     * URL 경로 반환 (웹 접근용)
     */
    public function getUrl(string $relativePath, string $storedName): string
    {
        return '/storage/' . $relativePath . '/' . $storedName;
    }

    /**
     * 도메인별 총 사용량 계산 (bytes)
     */
    public function getDomainUsage(int $domainId): int
    {
        $domainPath = $this->basePath . '/D' . $domainId;

        if (!is_dir($domainPath)) {
            return 0;
        }

        return $this->getDirectorySize($domainPath);
    }

    /**
     * 확장자 허용 여부 확인
     */
    public function isExtensionAllowed(string $extension, array $allowed = []): bool
    {
        if (empty($allowed)) {
            $allowed = $this->defaultAllowedExtensions;
        }

        return in_array(strtolower($extension), array_map('strtolower', $allowed), true);
    }

    /**
     * 위험한 확장자인지 확인
     */
    public function isDangerousExtension(string $extension): bool
    {
        return in_array(strtolower($extension), $this->dangerousExtensions, true);
    }

    /**
     * 저장 파일명 생성
     */
    private function generateStoredName(string $originalName, string $extension): string
    {
        $hash = md5(uniqid($originalName, true) . microtime(true) . random_bytes(8));
        return $hash . '.' . $extension;
    }

    /**
     * 디렉토리 생성 (없으면)
     */
    private function ensureDirectory(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        return mkdir($path, 0755, true);
    }

    /**
     * 디렉토리 크기 계산 (재귀)
     */
    private function getDirectorySize(string $path): int
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
