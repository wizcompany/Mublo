<?php

namespace Mublo\Contract\Fcm;

use Mublo\Core\Result\Result;

/**
 * 고객 전화번호를 Android 앱 전화번호부에 저장하는 컨트랙트.
 *
 * Rental/Shop 등 패키지가 FCM·Firebase 세부를 몰라도 "이 번호를 앱에 저장해줘"
 * 라고 요청할 수 있도록 플러그인 경계를 추상화한다.
 *
 * 저장 대상 기기는 DeviceBindingInterface 의 resolveDeviceForPhone 결과를 따른다.
 * 바인딩이 없으면 구현체가 주 기기(is_primary) 를 선택해 새 바인딩을 생성.
 *
 * 구현체: MubloFcm 플러그인 (Phase 1 에서는 기존 Rental FcmContactService shim 으로 위임).
 */
interface ContactSaveInterface
{
    /**
     * 전화번호부 저장 요청을 생성하고 대상 기기에 FCM 푸시를 보낸다.
     *
     * @param int    $domainId
     * @param string $phone
     * @param string $name          전화번호부에 표시될 이름
     * @param array  $meta          ['order_id' => int, 'consultation_id' => int,
     *                               'requested_by' => int(member_id), 'overwrite' => bool,
     *                               'device_id' => int (지정 시 해당 기기로만 푸시)]
     * @return Result data: [
     *   'contact_id' => int,
     *   'device_id'  => int,
     *   'status'     => 'pending'|'sent'|'saved'|'failed',
     * ]
     */
    public function saveContact(
        int $domainId,
        string $phone,
        string $name,
        array $meta = []
    ): Result;
}
