<?php

namespace Mublo\Contract\Fcm;

/**
 * 전화번호 ↔ FCM 기기 바인딩 컨트랙트.
 *
 * "이 고객은 어느 매장/기사(= 어느 물리 기기)가 담당하는가" 라는 도메인 사실을
 * 플러그인에 위임하기 위한 인터페이스. Rental/Shop/Board 등 패키지는
 * FCM 내부 세부를 몰라도 이 인터페이스만으로 바인딩을 조회·수정할 수 있다.
 *
 * 구현체: MubloFcm 플러그인 — `plugin_mublo_fcm_phone_bindings` 테이블.
 *
 * 등록 위치: ContractRegistry — MubloFcmProvider::boot() 에서 싱글톤 등록.
 *
 * 호출 예시:
 * ```php
 * $binding = $registry->get(DeviceBindingInterface::class);
 * $deviceId = $binding->resolveDeviceForPhone($domainId, $phone);
 * if ($deviceId === null) {
 *     $binding->bind($domainId, $phone, $primaryDeviceId, ['order_id' => $orderId]);
 * }
 * ```
 */
interface DeviceBindingInterface
{
    /**
     * 전화번호에 바인딩된 기기 ID 조회.
     *
     * @param int    $domainId
     * @param string $phone   정규화되지 않은 원본도 허용 — 구현체가 내부 정규화 책임
     * @return int|null 바인딩 없으면 null
     */
    public function resolveDeviceForPhone(int $domainId, string $phone): ?int;

    /**
     * 전화번호 ↔ 기기 바인딩 생성 또는 갱신.
     *
     * 이미 같은 (domain_id, phone) 바인딩이 있으면 device_id 를 업데이트하지 않고
     * last_used_at 만 갱신하는 upsert 로 동작한다. 명시적 기기 교체는 rebind() 사용.
     *
     * @param int    $domainId
     * @param string $phone
     * @param int    $deviceId
     * @param array  $meta    ['order_id' => int, 'consultation_id' => int] 등 추적용
     */
    public function bind(int $domainId, string $phone, int $deviceId, array $meta = []): void;

    /**
     * 바인딩 해제 (is_active = 0).
     */
    public function unbind(int $domainId, string $phone): void;

    /**
     * 기기 교체 — 기존 바인딩을 새 기기로 이동.
     * 기기 장애·매장 이관 등 명시적 재배정 시 호출.
     */
    public function rebind(int $domainId, string $phone, int $newDeviceId): void;
}
